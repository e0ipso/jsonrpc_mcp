<?php

declare(strict_types=1);

namespace Drupal\jsonrpc_mcp\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jsonrpc\Exception\JsonRpcException;
use Drupal\jsonrpc\HandlerInterface;
use Drupal\jsonrpc\JsonRpcObject\ParameterBag;
use Drupal\jsonrpc\JsonRpcObject\Request as RpcRequest;
use Drupal\jsonrpc_mcp\Service\McpToolDiscoveryService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for per-tool MCP invocation endpoints.
 */
class McpToolInvokeController extends ControllerBase {

  /**
   * Constructs a new McpToolInvokeController.
   *
   * @param \Drupal\jsonrpc_mcp\Service\McpToolDiscoveryService $toolDiscovery
   *   The tool discovery service.
   * @param \Drupal\jsonrpc\HandlerInterface $handler
   *   The JSON-RPC handler.
   */
  public function __construct(
    protected McpToolDiscoveryService $toolDiscovery,
    protected HandlerInterface $handler,
  ) {
    // Entity type manager and current user are available via ControllerBase.
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $controller = new self(
      $container->get('jsonrpc_mcp.tool_discovery'),
      $container->get('jsonrpc.handler'),
    );
    // Set ControllerBase properties.
    $controller->setEntityTypeManager($container->get('entity_type.manager'));
    $controller->setCurrentUser($container->get('current_user'));
    return $controller;
  }

  /**
   * Invokes a specific MCP tool with JSON-RPC payload.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request.
   * @param string $tool_name
   *   The tool name from the route.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
  public function invoke(Request $request, string $tool_name): Response {
    // Load tool metadata to check authentication requirements.
    $tools = $this->toolDiscovery->discoverTools();

    if (!isset($tools[$tool_name])) {
      return new JsonResponse([
        'jsonrpc' => '2.0',
        'error' => [
          'code' => -32601,
          'message' => 'Method not found',
        ],
        'id' => NULL,
      ], 404);
    }

    $method = $tools[$tool_name];
    $mcp_data = $this->extractMcpToolData($method);
    $auth_level = $mcp_data['annotations']['auth']['level'] ?? NULL;

    // Check if authentication is required.
    if ($auth_level === 'required' && $this->currentUser()->isAnonymous()) {
      return new Response('', 401, [
        'WWW-Authenticate' => 'Bearer realm="MCP Tools"',
        'Cache-Control' => 'no-store',
        'Pragma' => 'no-cache',
      ]);
    }

    // Detect OAuth2 authentication.
    $authorization = $request->headers->get('Authorization');
    $is_bearer = $authorization && str_starts_with($authorization, 'Bearer ');

    if ($is_bearer) {
      // Extract token value.
      $token_value = substr($authorization, 7);

      // Load token entity.
      $token_storage = $this->entityTypeManager()->getStorage('oauth2_token');
      $tokens = $token_storage->loadByProperties(['value' => $token_value]);
      $token = $tokens ? reset($tokens) : NULL;

      // Validate token.
      if (!$token || $token->isRevoked() || $token->get('expire')->value < time()) {
        return new Response('', 401, [
          'WWW-Authenticate' => 'Bearer realm="MCP Tools", error="invalid_token", error_description="The access token is invalid or expired"',
          'Cache-Control' => 'no-store',
        ]);
      }

      // Check required scopes if defined.
      $required_scopes = $mcp_data['annotations']['auth']['scopes'] ?? [];

      if (!empty($required_scopes)) {
        // Get token scopes.
        $token_scopes = $token->get('scopes')->referencedEntities();
        $token_scope_ids = array_map(fn($scope) => $scope->id(), $token_scopes);

        // Find missing scopes.
        $missing_scopes = array_diff($required_scopes, $token_scope_ids);

        if (!empty($missing_scopes)) {
          $scope_string = implode(' ', $missing_scopes);
          return new Response('', 403, [
            'WWW-Authenticate' => sprintf(
              'Bearer realm="MCP Tools", error="insufficient_scope", scope="%s"',
              $scope_string
            ),
            'Cache-Control' => 'no-store',
          ]);
        }
      }
    }

    // If scopes are required but user not using OAuth2, require OAuth2.
    $required_scopes = $mcp_data['annotations']['auth']['scopes'] ?? [];
    if (!empty($required_scopes) && !$is_bearer) {
      return new Response('', 401, [
        'WWW-Authenticate' => 'Bearer realm="MCP Tools"',
        'Cache-Control' => 'no-store',
      ]);
    }

    // Extract JSON-RPC payload based on HTTP method.
    if ($request->getMethod() === 'GET') {
      $query_payload = $request->query->get('query');

      if (!$query_payload) {
        return new JsonResponse([
          'jsonrpc' => '2.0',
          'error' => [
            'code' => -32600,
            'message' => 'Invalid Request: Missing query parameter',
          ],
          'id' => NULL,
        ], 400);
      }

      try {
        $rpc_payload = Json::decode($query_payload);
      }
      catch (\Exception $e) {
        return new JsonResponse([
          'jsonrpc' => '2.0',
          'error' => [
            'code' => -32700,
            'message' => 'Parse error',
          ],
          'id' => NULL,
        ], 400);
      }
    }
    else {
      // POST request.
      $content = $request->getContent();

      try {
        $rpc_payload = Json::decode($content);
      }
      catch (\Exception $e) {
        return new JsonResponse([
          'jsonrpc' => '2.0',
          'error' => [
            'code' => -32700,
            'message' => 'Parse error',
          ],
          'id' => NULL,
        ], 400);
      }

      // Validate JSON decode success.
      if ($rpc_payload === NULL || !is_array($rpc_payload)) {
        return new JsonResponse([
          'jsonrpc' => '2.0',
          'error' => [
            'code' => -32700,
            'message' => 'Parse error',
          ],
          'id' => NULL,
        ], 400);
      }
    }

    // Validate JSON decode success for both GET and POST.
    if ($rpc_payload === NULL || !is_array($rpc_payload)) {
      return new JsonResponse([
        'jsonrpc' => '2.0',
        'error' => [
          'code' => -32700,
          'message' => 'Parse error',
        ],
        'id' => NULL,
      ], 400);
    }

    // Validate JSON-RPC structure.
    if (!isset($rpc_payload['jsonrpc']) || $rpc_payload['jsonrpc'] !== '2.0') {
      return new JsonResponse([
        'jsonrpc' => '2.0',
        'error' => [
          'code' => -32600,
          'message' => 'Invalid Request',
        ],
        'id' => $rpc_payload['id'] ?? NULL,
      ], 400);
    }

    // Override method with route tool name (ignore any method in payload).
    $rpc_payload['method'] = $tool_name;

    return $this->delegateToJsonRpc($rpc_payload);
  }

  /**
   * Delegates execution to JSON-RPC handler.
   *
   * @param array $rpc_payload
   *   The JSON-RPC payload.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
  protected function delegateToJsonRpc(array $rpc_payload): Response {
    try {
      $version = $this->handler::supportedVersion();
      $params = new ParameterBag($rpc_payload['params'] ?? []);
      $id = $rpc_payload['id'] ?? NULL;
      $is_notification = $id === NULL;

      $rpc_request = new RpcRequest(
        $version,
        $rpc_payload['method'],
        $is_notification,
        $id,
        $params
      );

      $rpc_responses = $this->handler->batch([$rpc_request]);

      if (empty($rpc_responses)) {
        // This is a notification (no response expected).
        return new Response('', 204);
      }

      $rpc_response = reset($rpc_responses);

      // Return JSON-RPC response as-is (includes error or result).
      return new JsonResponse($rpc_response->getSerializedObject());

    }
    catch (JsonRpcException $e) {
      return new JsonResponse([
        'jsonrpc' => '2.0',
        'error' => [
          'code' => $e->getCode(),
          'message' => $e->getMessage(),
        ],
        'id' => $rpc_payload['id'] ?? NULL,
      ], 500);
    }
  }

  /**
   * Extracts McpTool attribute data via reflection.
   *
   * @param \Drupal\jsonrpc\MethodInterface $method
   *   The JSON-RPC method.
   *
   * @return array
   *   Associative array with 'title' and 'annotations' keys.
   */
  protected function extractMcpToolData($method): array {
    // Ensure the class is defined.
    $class = $method->getClass();
    if (!$class) {
      return ['title' => NULL, 'annotations' => NULL];
    }

    // Use reflection to read the McpTool attribute.
    $reflection = new \ReflectionClass($class);
    $attributes = $reflection->getAttributes(\Drupal\jsonrpc_mcp\Attribute\McpTool::class);

    if (empty($attributes)) {
      return ['title' => NULL, 'annotations' => NULL];
    }

    // Get the first McpTool attribute instance.
    $mcp_tool = $attributes[0]->newInstance();

    return [
      'title' => $mcp_tool->title,
      'annotations' => $mcp_tool->annotations,
    ];
  }

}
