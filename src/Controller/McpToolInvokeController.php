<?php

declare(strict_types=1);

namespace Drupal\jsonrpc_mcp\Controller;

use Drupal\jsonrpc_mcp\Attribute\McpTool;
use Drupal\simple_oauth\Entity\Oauth2TokenInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\jsonrpc\Exception\JsonRpcException;
use Drupal\jsonrpc\HandlerInterface;
use Drupal\jsonrpc\JsonRpcObject\Error;
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
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jsonrpc_mcp.tool_discovery'),
      $container->get('jsonrpc.handler'),
    );
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
    $tools = $this->toolDiscovery->discoverTools();

    if (!isset($tools[$tool_name])) {
      return $this->createErrorResponse(
        Error::METHOD_NOT_FOUND,
        'Method not found',
        NULL,
        404
      );
    }

    $method = $tools[$tool_name];
    $mcp_data = $this->extractMcpToolData($method);

    $auth_response = $this->checkAuthenticationRequirements($mcp_data, $request);
    if ($auth_response) {
      return $auth_response;
    }

    $rpc_payload_result = $this->extractRpcPayload($request);
    if ($rpc_payload_result instanceof Response) {
      return $rpc_payload_result;
    }

    $rpc_payload_result['method'] = $tool_name;

    return $this->delegateToJsonRpc($rpc_payload_result);
  }

  /**
   * Extracts JSON-RPC payload from the request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request.
   *
   * @return array|\Symfony\Component\HttpFoundation\Response
   *   The RPC payload array or error response.
   */
  protected function extractRpcPayload(Request $request): array|Response {
    $rpc_payload = NULL;

    if ($request->getMethod() === 'GET') {
      $query_payload = $request->query->get('query');

      if (!$query_payload) {
        return $this->createErrorResponse(
          Error::INVALID_REQUEST,
          'Invalid Request: Missing query parameter'
        );
      }

      try {
        $rpc_payload = Json::decode($query_payload);
      }
      catch (\Exception $e) {
        return $this->createErrorResponse(Error::PARSE_ERROR, 'Parse error');
      }
    }
    else {
      $content = $request->getContent();

      try {
        $rpc_payload = Json::decode($content);
      }
      catch (\Exception $e) {
        return $this->createErrorResponse(Error::PARSE_ERROR, 'Parse error');
      }
    }

    if ($rpc_payload === NULL || !is_array($rpc_payload)) {
      return $this->createErrorResponse(Error::PARSE_ERROR, 'Parse error');
    }

    if (!isset($rpc_payload['jsonrpc']) || $rpc_payload['jsonrpc'] !== '2.0') {
      return $this->createErrorResponse(
        Error::INVALID_REQUEST,
        'Invalid Request',
        $rpc_payload['id'] ?? NULL
      );
    }

    return $rpc_payload;
  }

  /**
   * Validates OAuth2 token and returns token or error response.
   *
   * @param string $token_value
   *   The Bearer token value.
   *
   * @return \Drupal\simple_oauth\Entity\Oauth2TokenInterface|\Symfony\Component\HttpFoundation\Response
   *   The validated token or error response.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function validateOauth2Token(string $token_value): Oauth2TokenInterface|Response {
    $token_storage = $this->entityTypeManager()->getStorage('oauth2_token');
    // @phpstan-ignore-next-line method.alreadyNarrowedType
    $token_ids = $token_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('value', $token_value)
      ->condition('expire', time(), '>')
      ->execute();

    if (empty($token_ids)) {
      return new Response('', 401, [
        'WWW-Authenticate' => 'Bearer realm="MCP Tools", error="invalid_token", error_description="The access token is invalid or expired"',
        'Cache-Control' => 'no-store',
      ]);
    }

    $tokens = $token_storage->loadMultiple($token_ids);
    $token = reset($tokens);

    if (!$token instanceof Oauth2TokenInterface || $token->isRevoked()) {
      return new Response('', 401, [
        'WWW-Authenticate' => 'Bearer realm="MCP Tools", error="invalid_token", error_description="The access token is invalid or expired"',
        'Cache-Control' => 'no-store',
      ]);
    }

    return $token;
  }

  /**
   * Validates OAuth2 token scopes against required scopes.
   *
   * @param \Drupal\simple_oauth\Entity\Oauth2TokenInterface $token
   *   The OAuth2 token.
   * @param array $required_scopes
   *   Required scope IDs.
   *
   * @return \Symfony\Component\HttpFoundation\Response|null
   *   Error response if scopes insufficient, NULL otherwise.
   */
  protected function validateOAuth2Scopes(Oauth2TokenInterface $token, array $required_scopes): ?Response {
    if (empty($required_scopes)) {
      return NULL;
    }

    $token_scopes = $token->get('scopes')->referencedEntities();
    $token_scope_ids = array_map(fn($scope) => $scope->id(), $token_scopes);
    $missing_scopes = array_diff($required_scopes, $token_scope_ids);

    if (empty($missing_scopes)) {
      return NULL;
    }

    $scope_string = implode(' ', $missing_scopes);
    return new Response('', 403, [
      'WWW-Authenticate' => sprintf(
        'Bearer realm="MCP Tools", error="insufficient_scope", scope="%s"',
        $scope_string
      ),
      'Cache-Control' => 'no-store',
    ]);
  }

  /**
   * Checks authentication requirements based on MCP tool annotations.
   *
   * @param array $mcp_data
   *   MCP tool metadata array.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request.
   *
   * @return \Symfony\Component\HttpFoundation\Response|null
   *   Error response if auth fails, NULL if auth passes.
   */
  protected function checkAuthenticationRequirements(array $mcp_data, Request $request): ?Response {
    $auth_level = $mcp_data['annotations']['auth']['level'] ?? NULL;
    $required_scopes = $mcp_data['annotations']['auth']['scopes'] ?? [];

    if ($auth_level === 'required' && $this->currentUser()->isAnonymous()) {
      return new Response('', 401, [
        'WWW-Authenticate' => 'Bearer realm="MCP Tools"',
        'Cache-Control' => 'no-store',
        'Pragma' => 'no-cache',
      ]);
    }

    $authorization = $request->headers->get('Authorization');
    $is_bearer = $authorization && str_starts_with($authorization, 'Bearer ');

    if (!empty($required_scopes) && !$is_bearer) {
      return new Response('', 401, [
        'WWW-Authenticate' => 'Bearer realm="MCP Tools"',
        'Cache-Control' => 'no-store',
      ]);
    }

    if (!$is_bearer) {
      return NULL;
    }

    $token_value = substr($authorization, 7);
    $token_result = $this->validateOauth2Token($token_value);

    if ($token_result instanceof Response) {
      return $token_result;
    }

    return $this->validateOAuth2Scopes($token_result, $required_scopes);
  }

  /**
   * Creates a JSON-RPC error response.
   *
   * @param int $code
   *   The error code.
   * @param string $message
   *   The error message.
   * @param mixed $id
   *   The request ID.
   * @param int $http_status
   *   HTTP status code.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The error response.
   */
  protected function createErrorResponse(int $code, string $message, mixed $id = NULL, int $http_status = 400): JsonResponse {
    return new JsonResponse([
      'jsonrpc' => '2.0',
      'error' => [
        'code' => $code,
        'message' => $message,
      ],
      'id' => $id,
    ], $http_status);
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
    $attributes = $reflection->getAttributes(McpTool::class);

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
