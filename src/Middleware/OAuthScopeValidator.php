<?php

declare(strict_types=1);

namespace Drupal\jsonrpc_mcp\Middleware;

use Drupal\Component\Serialization\Json;
use Drupal\jsonrpc_mcp\Service\McpToolDiscoveryService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Validates OAuth scopes for MCP tool invocations.
 */
class OAuthScopeValidator implements HttpKernelInterface {

  /**
   * Constructs a new OAuthScopeValidator.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $httpKernel
   *   The wrapped HTTP kernel.
   * @param \Drupal\jsonrpc_mcp\Service\McpToolDiscoveryService $toolDiscovery
   *   The tool discovery service.
   */
  public function __construct(
    protected HttpKernelInterface $httpKernel,
    protected McpToolDiscoveryService $toolDiscovery,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = TRUE): Response {
    // Only validate MCP tool invocations.
    if ($request->getPathInfo() !== '/mcp/tools/invoke') {
      return $this->httpKernel->handle($request, $type, $catch);
    }

    // Extract tool name from request.
    $content = $request->getContent();
    $data = Json::decode($content);
    $tool_name = $data['name'] ?? NULL;

    if (!$tool_name) {
      return $this->httpKernel->handle($request, $type, $catch);
    }

    // Get tool definition.
    $tools = $this->toolDiscovery->discoverTools();

    if (!isset($tools[$tool_name])) {
      return $this->httpKernel->handle($request, $type, $catch);
    }

    $tool = $tools[$tool_name];

    // Check if authentication is required.
    if (!method_exists($tool, 'requiresAuthentication') || !$tool->requiresAuthentication()) {
      return $this->httpKernel->handle($request, $type, $catch);
    }

    // Get token scopes.
    $token_scopes = $this->getTokenScopes($request);

    // Validate scopes.
    $required_scopes = method_exists($tool, 'getRequiredScopes') ? $tool->getRequiredScopes() : [];
    $missing_scopes = array_diff($required_scopes, $token_scopes);

    if (!empty($missing_scopes)) {
      return new JsonResponse([
        'jsonrpc' => '2.0',
        'error' => [
          'code' => -32000,
          'message' => 'Insufficient OAuth scopes',
          'data' => [
            'required_scopes' => $required_scopes,
            'missing_scopes' => array_values($missing_scopes),
            'current_scopes' => $token_scopes,
          ],
        ],
        'id' => $data['id'] ?? NULL,
      ], 403);
    }

    return $this->httpKernel->handle($request, $type, $catch);
  }

  /**
   * Extracts OAuth scopes from the request token.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return array
   *   Array of scope strings.
   */
  protected function getTokenScopes(Request $request): array {
    $authorization = $request->headers->get('Authorization');
    if (!$authorization || !str_starts_with($authorization, 'Bearer ')) {
      return [];
    }

    $token = substr($authorization, 7);

    // Use Simple OAuth to decode the token and extract scopes.
    if (!\Drupal::hasService('simple_oauth.oauth2_storage')) {
      return [];
    }

    try {
      $oauth_storage = \Drupal::service('simple_oauth.oauth2_storage');
      $access_token = $oauth_storage->getAccessToken($token);

      if (!$access_token) {
        return [];
      }

      // Extract scopes from token.
      $scopes = $access_token->getScopes();
      return array_map(fn($scope) => $scope->getIdentifier(), $scopes);
    }
    catch (\Exception $e) {
      // Token parsing failed, assume no scopes.
      return [];
    }
  }

}
