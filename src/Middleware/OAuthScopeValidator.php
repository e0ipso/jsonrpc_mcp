<?php

declare(strict_types=1);

namespace Drupal\jsonrpc_mcp\Middleware;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityTypeManagerInterface;
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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    protected HttpKernelInterface $httpKernel,
    protected McpToolDiscoveryService $toolDiscovery,
    protected EntityTypeManagerInterface $entityTypeManager,
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

    $token_value = substr($authorization, 7);

    try {
      // Load token entity by value.
      $tokens = $this->entityTypeManager
        ->getStorage('oauth2_token')
        ->loadByProperties(['value' => $token_value]);

      if (empty($tokens)) {
        return [];
      }

      /** @var \Drupal\simple_oauth\Entity\Oauth2TokenInterface $token */
      $token = reset($tokens);

      // Check if token is revoked or expired.
      if ($token->isRevoked() || $token->get('expire')->value < time()) {
        return [];
      }

      // Extract scopes from token.
      /** @var \Drupal\simple_oauth\Plugin\Field\FieldType\Oauth2ScopeReferenceItemListInterface $scopes_field */
      $scopes_field = $token->get('scopes');
      $scope_ids = [];
      foreach ($scopes_field->getScopes() as $scope) {
        $scope_ids[] = $scope;
      }

      return $scope_ids;
    }
    catch (\Exception $e) {
      // Token loading failed, assume no scopes.
      return [];
    }
  }

}
