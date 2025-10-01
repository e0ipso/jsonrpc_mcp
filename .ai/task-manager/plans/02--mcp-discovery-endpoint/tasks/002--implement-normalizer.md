---
id: 2
group: 'mcp-discovery-endpoint'
dependencies: []
status: 'pending'
created: '2025-10-01'
skills: ['json-schema', 'php-reflection-api']
---

# Implement McpToolNormalizer

## Objective

Create the normalizer service that transforms JSON-RPC method definitions into MCP-compliant tool schema format with proper JSON Schema conversion.

## Skills Required

- JSON Schema specification (understanding JSON Schema Draft 7 structure)
- PHP Reflection API (reading attribute arguments)
- Drupal TranslatableMarkup handling (converting to strings)

## Acceptance Criteria

- [ ] File `src/Normalizer/McpToolNormalizer.php` exists with correct namespace
- [ ] Implements `normalize(MethodInterface $method): array` method
- [ ] Converts `id` → `name`
- [ ] Converts `usage` (TranslatableMarkup) → `description` (string)
- [ ] Transforms `params` array → `inputSchema` (JSON Schema object)
- [ ] Includes `properties` and `required` arrays in inputSchema
- [ ] Adds `outputSchema` if method implements outputSchema() method
- [ ] Extracts `title` and `annotations` from McpTool attribute
- [ ] Returns array matching MCP tool schema specification

## Technical Requirements

**File Location:** `src/Normalizer/McpToolNormalizer.php`

**Class Structure:**

```php
namespace Drupal\jsonrpc_mcp\Normalizer;

use Drupal\jsonrpc\MethodInterface;
use Drupal\jsonrpc_mcp\Attribute\McpTool;

class McpToolNormalizer {

  /**
   * Normalizes a JSON-RPC method to MCP tool format.
   *
   * @param \Drupal\jsonrpc\MethodInterface $method
   *   The JSON-RPC method to normalize.
   *
   * @return array
   *   MCP-compliant tool schema.
   */
  public function normalize(MethodInterface $method): array {
    $definition = $method->getPluginDefinition();

    // Build base tool schema from JsonRpcMethod
    $tool = [
      'name' => $definition['id'],
      'description' => (string) $definition['usage'],
      'inputSchema' => $this->buildInputSchema($definition['params'] ?? []),
    ];

    // Add outputSchema if available
    if (method_exists($method, 'outputSchema')) {
      $tool['outputSchema'] = $method::outputSchema();
    }

    // Extract McpTool attribute data
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
   */
  protected function buildInputSchema(array $params): array {
    $properties = [];
    $required = [];

    foreach ($params as $param_name => $param_def) {
      $properties[$param_name] = $param_def->getSchema();

      if ($param_def->getDescription()) {
        $properties[$param_name]['description'] = (string) $param_def->getDescription();
      }

      if ($param_def->isRequired()) {
        $required[] = $param_name;
      }
    }

    $schema = [
      'type' => 'object',
      'properties' => $properties,
    ];

    if (!empty($required)) {
      $schema['required'] = $required;
    }

    return $schema;
  }

  /**
   * Extracts McpTool attribute data via reflection.
   */
  protected function extractMcpToolData(MethodInterface $method): array {
    $reflection = new \ReflectionClass($method->getPluginDefinition()['class']);
    $attributes = $reflection->getAttributes(McpTool::class);

    if (empty($attributes)) {
      return ['title' => null, 'annotations' => null];
    }

    $mcp_tool = $attributes[0]->newInstance();
    return [
      'title' => $mcp_tool->title,
      'annotations' => $mcp_tool->annotations,
    ];
  }
}
```

**Schema Transformation Logic:**

- Extract JsonRpcParameterDefinition objects from params array
- Use `getSchema()` to get parameter schema
- Use `getDescription()` and convert TranslatableMarkup to string
- Use `isRequired()` to build required array
- Wrap in JSON Schema object format with `type: 'object'`

## Input Dependencies

- Plan 01 completed (McpTool attribute exists)
- jsonrpc module's MethodInterface and parameter definitions available

## Output Artifacts

- `src/Normalizer/McpToolNormalizer.php` - Working normalizer service
- Service will be defined in task 004 (services.yml)

## Implementation Notes

- Normalizer should be stateless - no internal state
- Handle empty params array (no parameters)
- TranslatableMarkup conversion uses string cast: `(string) $markup`
- The outputSchema is optional - only add if method implements it
- Use reflection to instantiate McpTool attribute and read properties
