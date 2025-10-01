---
id: 1
group: 'mcp-tool-attribute'
dependencies: []
status: 'completed'
created: '2025-10-01'
skills: ['php-attributes', 'drupal-plugins']
---

# Implement McpTool PHP Attribute Class

## Objective

Create the `#[McpTool]` PHP 8 attribute class that extends Drupal's plugin system to mark JSON-RPC methods for MCP exposure.

## Skills Required

- PHP 8.1+ attributes (defining attributes with `#[\Attribute]` syntax)
- Drupal plugin system architecture (extending `Drupal\Component\Plugin\Attribute\Plugin`)

## Acceptance Criteria

- [ ] File `src/Attribute/McpTool.php` exists with correct namespace `Drupal\jsonrpc_mcp\Attribute`
- [ ] Class extends `Drupal\Component\Plugin\Attribute\Plugin`
- [ ] Uses `#[\Attribute(\Attribute::TARGET_CLASS)]` restriction
- [ ] Constructor accepts optional `title` (string) and `annotations` (array) parameters
- [ ] Properties are readonly for immutability
- [ ] Validation rejects indexed arrays (lists) for annotations parameter
- [ ] Accepts associative arrays including nested structures for annotations

## Technical Requirements

**Class Structure:**

```php
namespace Drupal\jsonrpc_mcp\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;

#[\Attribute(\Attribute::TARGET_CLASS)]
class McpTool extends Plugin {
  public function __construct(
    public readonly ?string $title = null,
    public readonly ?array $annotations = null,
  ) {
    // Validation: annotations must be associative array if provided
    if ($annotations !== null && array_is_list($annotations)) {
      throw new \InvalidArgumentException('McpTool annotations must be an associative array');
    }
  }
}
```

**Validation Logic:**

- Use `array_is_list()` to detect indexed arrays
- Throw `\InvalidArgumentException` for invalid annotations
- Allow null for both parameters (optional)
- No validation on title (allow empty strings)

## Input Dependencies

None - this is the foundational component.

## Output Artifacts

- `src/Attribute/McpTool.php` - Working attribute class that can be used in unit tests and future discovery service

## Implementation Notes

- Follow the exact pattern from jsonrpc module's `JsonRpcMethod` attribute
- Keep validation minimal - only reject clearly invalid structures
- The attribute is passive at this stage - it only stores metadata
- Discovery mechanism will be implemented in a separate plan
