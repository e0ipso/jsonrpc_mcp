<?php

declare(strict_types=1);

namespace Drupal\jsonrpc_mcp\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\jsonrpc\Exception\JsonRpcException;
use Drupal\jsonrpc\HandlerInterface;
use Drupal\jsonrpc\JsonRpcObject\ParameterBag;
use Drupal\jsonrpc\JsonRpcObject\Request as RpcRequest;
use Drupal\jsonrpc_mcp\Normalizer\McpToolNormalizer;
use Drupal\jsonrpc_mcp\Service\McpToolDiscoveryService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for MCP tools discovery endpoint.
 *
 * This controller handles the /mcp/tools/list HTTP endpoint, providing
 * MCP-compliant tool discovery with cursor-based pagination. It coordinates
 * the McpToolDiscoveryService and McpToolNormalizer to return JSON-RPC
 * methods marked with the #[McpTool] attribute in MCP tool schema format.
 *
 * Cache tags used:
 * - jsonrpc_mcp:discovery: Invalidated when modules install/uninstall or
 *   when plugin definitions change.
 * - user.permissions: Automatically invalidated when permission system changes.
 */
class McpToolsController extends ControllerBase {

  /**
   * Constructs a new McpToolsController.
   *
   * @param \Drupal\jsonrpc_mcp\Service\McpToolDiscoveryService $toolDiscovery
   *   The MCP tool discovery service.
   * @param \Drupal\jsonrpc_mcp\Normalizer\McpToolNormalizer $normalizer
   *   The MCP tool normalizer service.
   * @param \Drupal\jsonrpc\HandlerInterface $handler
   *   The JSON-RPC handler service.
   */
  public function __construct(
    protected McpToolDiscoveryService $toolDiscovery,
    protected McpToolNormalizer $normalizer,
    protected HandlerInterface $handler,
  ) {}

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\jsonrpc_mcp\Controller\McpToolsController
   *   The controller.
   */
  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('jsonrpc_mcp.tool_discovery'),
      $container->get('jsonrpc_mcp.tool_normalizer'),
      $container->get('jsonrpc.handler'),
    );
  }

  /**
   * Returns MCP-compliant tool list.
   *
   * Handles the /mcp/tools/list endpoint, returning a paginated list of
   * tools in MCP-compliant format. Pagination uses cursor-based approach
   * with base64-encoded offsets.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request object.
   *
   * @return \Drupal\Core\Cache\CacheableJsonResponse
   *   JSON response with 'tools' array and 'nextCursor' field.
   */
  public function list(Request $request): CacheableJsonResponse {
    $cursor = $request->query->get('cursor');
    $tools = $this->toolDiscovery->discoverTools();

    // Apply pagination (simple offset-based for now).
    $page_size = 50;
    $offset = $cursor ? (int) base64_decode($cursor) : 0;
    $page_tools = array_slice($tools, $offset, $page_size, TRUE);

    // Normalize to MCP format.
    $normalized_tools = [];
    foreach ($page_tools as $method) {
      $normalized_tools[] = $this->normalizer->normalize($method);
    }

    // Calculate next cursor.
    $next_cursor = NULL;
    if (count($tools) > $offset + $page_size) {
      $next_cursor = base64_encode((string) ($offset + $page_size));
    }

    $response = new CacheableJsonResponse([
      'tools' => $normalized_tools,
      'nextCursor' => $next_cursor,
    ]);

    $cache_metadata = new CacheableMetadata();
    $cache_metadata->setCacheMaxAge(Cache::PERMANENT);
    $cache_metadata->setCacheTags(['jsonrpc_mcp:discovery', 'user.permissions']);
    $cache_metadata->setCacheContexts(['user', 'url.query_args:cursor']);

    $response->addCacheableDependency($cache_metadata);

    return $response;
  }

  /**
   * Returns detailed MCP-compliant tool description.
   *
   * Handles the /mcp/tools/describe endpoint, returning detailed schema
   * for a specific tool identified by the 'name' query parameter.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request object.
   *
   * @return \Drupal\Core\Cache\CacheableJsonResponse
   *   JSON response with 'tool' object or 'error' object.
   */
  public function describe(Request $request): CacheableJsonResponse {
    $name = $request->query->get('name');

    if (!$name) {
      $response = new CacheableJsonResponse([
        'error' => [
          'code' => 'missing_parameter',
          'message' => 'Required parameter "name" is missing',
        ],
      ], 400);

      $cache_metadata = new CacheableMetadata();
      $cache_metadata->setCacheMaxAge(0);

      $response->addCacheableDependency($cache_metadata);

      return $response;
    }

    $tools = $this->toolDiscovery->discoverTools();

    if (!isset($tools[$name])) {
      $response = new CacheableJsonResponse([
        'error' => [
          'code' => 'tool_not_found',
          'message' => sprintf("Tool '%s' not found or access denied", $name),
        ],
      ], 404);

      $cache_metadata = new CacheableMetadata();
      $cache_metadata->setCacheMaxAge(0);

      $response->addCacheableDependency($cache_metadata);

      return $response;
    }

    $normalized_tool = $this->normalizer->normalize($tools[$name]);

    $response = new CacheableJsonResponse([
      'tool' => $normalized_tool,
    ]);

    $cache_metadata = new CacheableMetadata();
    $cache_metadata->setCacheMaxAge(Cache::PERMANENT);
    $cache_metadata->setCacheTags(['jsonrpc_mcp:discovery', 'user.permissions']);
    $cache_metadata->setCacheContexts(['user', 'url.query_args:name']);

    $response->addCacheableDependency($cache_metadata);

    return $response;
  }

  /**
   * Invokes an MCP tool with JSON-RPC execution.
   *
   * Handles the /mcp/tools/invoke endpoint, translating MCP-format requests
   * to JSON-RPC format, executing via the JSON-RPC handler, and returning
   * MCP-format responses.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request object.
   *
   * @return \Drupal\Core\Cache\CacheableJsonResponse
   *   JSON response with 'result' object or 'error' object.
   */
  public function invoke(Request $request): CacheableJsonResponse {
    // Parse JSON request body.
    $content = $request->getContent();
    try {
      $data = Json::decode($content);
    }
    catch (\Exception $e) {
      $response = new CacheableJsonResponse([
        'error' => [
          'code' => 'invalid_json',
          'message' => 'Request body must be valid JSON',
        ],
      ], 400);

      $cache_metadata = new CacheableMetadata();
      $cache_metadata->setCacheMaxAge(0);

      $response->addCacheableDependency($cache_metadata);

      return $response;
    }

    // Validate JSON decode success.
    if ($data === NULL || !is_array($data)) {
      $response = new CacheableJsonResponse([
        'error' => [
          'code' => 'invalid_json',
          'message' => 'Request body must be valid JSON',
        ],
      ], 400);

      $cache_metadata = new CacheableMetadata();
      $cache_metadata->setCacheMaxAge(0);

      $response->addCacheableDependency($cache_metadata);

      return $response;
    }

    // Validate required parameters.
    if (!isset($data['name']) || !is_string($data['name'])) {
      $response = new CacheableJsonResponse([
        'error' => [
          'code' => 'missing_parameter',
          'message' => 'Required parameter "name" is missing or invalid',
        ],
      ], 400);

      $cache_metadata = new CacheableMetadata();
      $cache_metadata->setCacheMaxAge(0);

      $response->addCacheableDependency($cache_metadata);

      return $response;
    }

    if (!isset($data['arguments']) || !is_array($data['arguments'])) {
      $response = new CacheableJsonResponse([
        'error' => [
          'code' => 'missing_parameter',
          'message' => 'Required parameter "arguments" is missing or invalid',
        ],
      ], 400);

      $cache_metadata = new CacheableMetadata();
      $cache_metadata->setCacheMaxAge(0);

      $response->addCacheableDependency($cache_metadata);

      return $response;
    }

    $name = $data['name'];
    $arguments = $data['arguments'];

    // Validate tool exists and is accessible.
    $tools = $this->toolDiscovery->discoverTools();

    if (!isset($tools[$name])) {
      $response = new CacheableJsonResponse([
        'error' => [
          'code' => 'tool_not_found',
          'message' => sprintf("Tool '%s' not found or access denied", $name),
        ],
      ], 404);

      $cache_metadata = new CacheableMetadata();
      $cache_metadata->setCacheMaxAge(0);

      $response->addCacheableDependency($cache_metadata);

      return $response;
    }

    // Execute via JSON-RPC handler.
    try {
      $version = $this->handler::supportedVersion();
      $params = new ParameterBag($arguments);
      $rpc_request = new RpcRequest($version, $name, FALSE, uniqid('mcp_', TRUE), $params);
      $rpc_responses = $this->handler->batch([$rpc_request]);

      // Extract result from JSON-RPC response.
      if (empty($rpc_responses)) {
        $response = new CacheableJsonResponse([
          'error' => [
            'code' => 'execution_error',
            'message' => 'Tool execution returned no response',
          ],
        ], 500);

        $cache_metadata = new CacheableMetadata();
        $cache_metadata->setCacheMaxAge(0);

        $response->addCacheableDependency($cache_metadata);

        return $response;
      }

      $rpc_response = reset($rpc_responses);

      if ($rpc_response->isErrorResponse()) {
        $error = $rpc_response->getError();
        $response = new CacheableJsonResponse([
          'error' => [
            'code' => 'execution_error',
            'message' => $error->getMessage(),
          ],
        ], 500);

        $cache_metadata = new CacheableMetadata();
        $cache_metadata->setCacheMaxAge(0);

        $response->addCacheableDependency($cache_metadata);

        return $response;
      }

      $response = new CacheableJsonResponse([
        'result' => $rpc_response->getResult(),
      ]);

      $cache_metadata = new CacheableMetadata();
      $cache_metadata->setCacheMaxAge(0);

      $response->addCacheableDependency($cache_metadata);

      return $response;
    }
    catch (JsonRpcException $e) {
      $response = new CacheableJsonResponse([
        'error' => [
          'code' => 'execution_error',
          'message' => sprintf('Tool execution failed: %s', $e->getMessage()),
        ],
      ], 500);

      $cache_metadata = new CacheableMetadata();
      $cache_metadata->setCacheMaxAge(0);

      $response->addCacheableDependency($cache_metadata);

      return $response;
    }
    catch (\Exception $e) {
      $response = new CacheableJsonResponse([
        'error' => [
          'code' => 'execution_error',
          'message' => sprintf('Tool execution failed: %s', $e->getMessage()),
        ],
      ], 500);

      $cache_metadata = new CacheableMetadata();
      $cache_metadata->setCacheMaxAge(0);

      $response->addCacheableDependency($cache_metadata);

      return $response;
    }
  }

}
