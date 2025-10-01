<?php

declare(strict_types=1);

namespace Drupal\jsonrpc_mcp\Normalizer;

use Drupal\jsonrpc\MethodInterface;
use Drupal\jsonrpc_mcp\Attribute\McpTool;

/**
 * Normalizes JSON-RPC methods to MCP tool format.
 *
 * This service transforms Drupal JSON-RPC method definitions into
 * Model Context Protocol (MCP) compliant tool schemas. It handles
 * the conversion of parameter definitions to JSON Schema format,
 * extracts MCP-specific metadata from the McpTool attribute, and
 * produces schemas compatible with MCP specification 2025-06-18.
 */
class McpToolNormalizer {

  /**
   * Normalizes a JSON-RPC method to MCP tool format.
   *
   * Transforms a JSON-RPC method definition into an MCP-compliant tool schema
   * by extracting metadata from both the JsonRpcMethod attribute (via the
   * MethodInterface) and the optional McpTool attribute.
   *
   * The returned array follows the MCP tool schema specification with:
   * - name: Unique identifier from the JSON-RPC method ID
   * - description: Human-readable description from the method's usage text
   * - inputSchema: JSON Schema (Draft 7) describing method parameters
   * - outputSchema: Optional JSON Schema describing return values
   * - title: Optional human-readable display name from McpTool attribute
   * - annotations: Optional metadata object from McpTool attribute
   *
   * @param \Drupal\jsonrpc\MethodInterface $method
   *   The JSON-RPC method to normalize.
   *
   * @return array
   *   MCP-compliant tool schema.
   */
  public function normalize(MethodInterface $method): array {
    // Build base tool schema from JsonRpcMethod.
    $tool = [
      'name' => $method->id(),
      'description' => (string) $method->getUsage(),
      'inputSchema' => $this->buildInputSchema($method->getParams() ?? []),
    ];

    // Add outputSchema if available.
    // The method class must define a static outputSchema() method.
    $class = $method->getClass();
    if ($class && method_exists($class, 'outputSchema')) {
      $output_schema = $class::outputSchema();
      if ($output_schema !== NULL) {
        $tool['outputSchema'] = $output_schema;
      }
    }

    // Extract McpTool attribute data.
    $mcp_data = $this->extractMcpToolData($method);
    if ($mcp_data['title']) {
      $tool['title'] = $mcp_data['title'];
    }
    if ($mcp_data['annotations']) {
      $tool['annotations'] = $mcp_data['annotations'];
    }

    return $tool;
  }

  /**
   * Builds JSON Schema inputSchema from params array.
   *
   * Converts an array of JsonRpcParameterDefinition objects into a JSON
   * Schema object suitable for MCP tool schemas. The schema follows JSON
   * Schema Draft 7 with type 'object', a properties map for each parameter,
   * and a required array listing mandatory parameters.
   *
   * Parameter descriptions (if provided as TranslatableMarkup) are converted
   * to strings and included in the property schemas.
   *
   * @param array $params
   *   Array of JsonRpcParameterDefinition objects keyed by parameter name.
   *
   * @return array
   *   JSON Schema object with properties and optional required array.
   */
  protected function buildInputSchema(array $params): array {
    $properties = [];
    $required = [];

    foreach ($params as $param_name => $param_def) {
      // Get the base schema from the parameter definition.
      $properties[$param_name] = $param_def->getSchema();

      // Add description if provided.
      if ($param_def->getDescription()) {
        $properties[$param_name]['description'] = (string) $param_def->getDescription();
      }

      // Track required parameters.
      if ($param_def->isRequired()) {
        $required[] = $param_name;
      }
    }

    // Build the JSON Schema object.
    $schema = [
      'type' => 'object',
      'properties' => $properties,
    ];

    // Only add required array if there are required parameters.
    if (!empty($required)) {
      $schema['required'] = $required;
    }

    return $schema;
  }

  /**
   * Extracts McpTool attribute data via reflection.
   *
   * Uses PHP's reflection API to read the McpTool attribute from the method's
   * plugin class. If the attribute is not present, returns null values for
   * both title and annotations.
   *
   * @param \Drupal\jsonrpc\MethodInterface $method
   *   The JSON-RPC method to extract McpTool data from.
   *
   * @return array
   *   Associative array with 'title' and 'annotations' keys.
   */
  protected function extractMcpToolData(MethodInterface $method): array {
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

    // Instantiate the attribute and extract properties.
    $mcp_tool = $attributes[0]->newInstance();
    return [
      'title' => $mcp_tool->title,
      'annotations' => $mcp_tool->annotations,
    ];
  }

}
