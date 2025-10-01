<?php

declare(strict_types=1);

namespace Drupal\jsonrpc_mcp\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jsonrpc_mcp\Normalizer\McpToolNormalizer;
use Drupal\jsonrpc_mcp\Service\McpToolDiscoveryService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for MCP tools discovery endpoint.
 *
 * This controller handles the /mcp/tools/list HTTP endpoint, providing
 * MCP-compliant tool discovery with cursor-based pagination. It coordinates
 * the McpToolDiscoveryService and McpToolNormalizer to return JSON-RPC
 * methods marked with the #[McpTool] attribute in MCP tool schema format.
 */
class McpToolsController extends ControllerBase {

  /**
   * Constructs a new McpToolsController.
   *
   * @param \Drupal\jsonrpc_mcp\Service\McpToolDiscoveryService $toolDiscovery
   *   The MCP tool discovery service.
   * @param \Drupal\jsonrpc_mcp\Normalizer\McpToolNormalizer $normalizer
   *   The MCP tool normalizer service.
   */
  public function __construct(
    protected McpToolDiscoveryService $toolDiscovery,
    protected McpToolNormalizer $normalizer,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('jsonrpc_mcp.tool_discovery'),
      $container->get('jsonrpc_mcp.tool_normalizer'),
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
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with 'tools' array and 'nextCursor' field.
   */
  public function list(Request $request): JsonResponse {
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

    return new JsonResponse([
      'tools' => $normalized_tools,
      'nextCursor' => $next_cursor,
    ]);
  }

}
