---
id: 3
group: 'mcp-tool-attribute'
dependencies: [1]
status: 'completed'
created: '2025-10-01'
skills: ['phpdoc']
---

# Add PHPDoc Documentation to McpTool Attribute

## Objective

Add comprehensive PHPDoc documentation to the McpTool attribute class, including class-level documentation with usage examples and property-level documentation.

## Skills Required

- PHPDoc documentation standards (Drupal coding standards for docblocks)

## Acceptance Criteria

- [ ] Class has complete PHPDoc block with description
- [ ] Class docblock includes `@Attribute` tag
- [ ] Class docblock includes complete usage example in `@code` block
- [ ] Constructor has PHPDoc block documenting parameters
- [ ] Both properties (`title`, `annotations`) have PHPDoc blocks
- [ ] Documentation explains the relationship with `#[JsonRpcMethod]` attribute
- [ ] Example shows dual-attribute usage pattern
- [ ] Documentation passes Drupal coding standards: `vendor/bin/phpcs --standard=Drupal src/Attribute/McpTool.php`

## Technical Requirements

**Class Documentation Pattern:**

```php
/**
 * Marks a JSON-RPC method for exposure as an MCP tool.
 *
 * This attribute should be applied alongside the #[JsonRpcMethod] attribute
 * to indicate that a JSON-RPC method should be discoverable by MCP clients.
 *
 * @Attribute
 *
 * Example usage:
 * @code
 * #[JsonRpcMethod(
 *   id: "cache.rebuild",
 *   usage: new TranslatableMarkup("Rebuilds the system cache"),
 *   access: ["administer site configuration"]
 * )]
 * #[McpTool(
 *   title: "Rebuild Drupal Cache",
 *   annotations: ['category' => 'system', 'destructive' => false]
 * )]
 * class CacheRebuild extends JsonRpcMethodBase { }
 * @endcode
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class McpTool extends Plugin {
```

**Property Documentation:**

- Document what `title` is used for (human-readable display name in MCP clients)
- Document what `annotations` is used for (arbitrary metadata for MCP tool schema)
- Include type information and nullability

**Constructor Documentation:**

- Document each parameter
- Note validation behavior for annotations parameter

## Input Dependencies

Requires task 1 completed: `src/Attribute/McpTool.php` must exist with working implementation.

## Output Artifacts

- Updated `src/Attribute/McpTool.php` with complete PHPDoc documentation
- Verification: `vendor/bin/phpcs --standard=Drupal src/Attribute/McpTool.php` returns no errors

## Implementation Notes

- Follow Drupal documentation standards exactly
- Use `@code` blocks for examples (not markdown code fences)
- Keep example concise but complete
- Show the dual-attribute pattern clearly
- Documentation should help developers understand when and how to use the attribute
