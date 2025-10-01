<?php

declare(strict_types=1);

namespace Drupal\jsonrpc_mcp\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;

/**
 * Defines an MCP Tool attribute for marking JSON-RPC methods.
 *
 * This attribute should be used alongside #[JsonRpcMethod] to mark methods
 * that should be exposed via the Model Context Protocol (MCP).
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class McpTool extends Plugin {

  /**
   * Constructs a new McpTool attribute.
   *
   * @param string|null $title
   *   Optional human-readable title for the MCP tool.
   * @param array|null $annotations
   *   Optional associative array of metadata annotations.
   *
   * @throws \InvalidArgumentException
   *   When annotations is an indexed array (list).
   */
  public function __construct(
    public readonly ?string $title = NULL,
    public readonly ?array $annotations = NULL,
  ) {
    // Validation: annotations must be associative array if provided.
    if ($annotations !== NULL && array_is_list($annotations)) {
      throw new \InvalidArgumentException('McpTool annotations must be an associative array');
    }
  }

}
