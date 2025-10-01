<?php

declare(strict_types=1);

namespace Drupal\jsonrpc_mcp\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;

/**
 * Marks a JSON-RPC method for exposure as an MCP tool.
 *
 * This attribute should be applied alongside the #[JsonRpcMethod] attribute
 * to indicate that a JSON-RPC method should be discoverable by MCP clients
 * (Model Context Protocol). The attribute provides additional metadata that
 * enhances the tool's representation in MCP tool schemas.
 *
 * The title property provides a human-readable display name for MCP clients,
 * while the annotations property allows arbitrary metadata to be included in
 * the MCP tool schema.
 *
 * @Attribute
 *
 * Example usage:
 * @code
 * use Drupal\Core\StringTranslation\TranslatableMarkup;
 * use Drupal\jsonrpc\Attribute\JsonRpcMethod;
 * use Drupal\jsonrpc_mcp\Attribute\McpTool;
 * use Drupal\jsonrpc\Plugin\JsonRpcMethodBase;
 *
 * #[JsonRpcMethod(
 *   id: "cache.rebuild",
 *   usage: new TranslatableMarkup("Rebuilds the system cache"),
 *   access: ["administer site configuration"]
 * )]
 * #[McpTool(
 *   title: "Rebuild Drupal Cache",
 *   annotations: ['category' => 'system', 'destructive' => false]
 * )]
 * class CacheRebuild extends JsonRpcMethodBase {
 *   // Implementation...
 * }
 * @endcode
 *
 * @see \Drupal\jsonrpc\Attribute\JsonRpcMethod
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class McpTool extends Plugin {

  /**
   * Optional human-readable title for the MCP tool.
   *
   * This title is used as the display name in MCP clients. If not provided,
   * clients will use the JSON-RPC method ID as the display name.
   *
   * @var string|null
   */
  public readonly ?string $title;

  /**
   * Optional associative array of metadata annotations.
   *
   * These annotations are included in the MCP tool schema as arbitrary
   * metadata. Common uses include categorization, capability flags, or
   * client-specific hints.
   *
   * Must be an associative array (not a list). Example:
   * ['category' => 'system', 'destructive' => false]
   *
   * @var array|null
   */
  public readonly ?array $annotations;

  /**
   * Constructs a new McpTool attribute.
   *
   * @param string|null $title
   *   Optional human-readable title for the MCP tool. Used as the display
   *   name in MCP clients.
   * @param array|null $annotations
   *   Optional associative array of metadata annotations to include in the
   *   MCP tool schema. Must be an associative array, not a list.
   *
   * @throws \InvalidArgumentException
   *   Thrown when annotations is provided as an indexed array (list) instead
   *   of an associative array.
   */
  public function __construct(
    ?string $title = NULL,
    ?array $annotations = NULL,
  ) {
    $this->title = $title;
    $this->annotations = $annotations;

    // Validation: annotations must be associative array if provided.
    if ($annotations !== NULL && array_is_list($annotations)) {
      throw new \InvalidArgumentException('McpTool annotations must be an associative array');
    }
  }

}
