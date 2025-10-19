---
id: 2
group: "plugin-system"
dependencies: [1]
status: "completed"
created: "2025-10-19"
skills:
  - php
  - drupal-backend
---
# Create McpToolInterface with Authentication Methods

## Objective
Define the interface contract for MCP tool plugins to expose authentication metadata and requirements.

## Skills Required
- **php**: Interface design with PHP 8.1+ type hints
- **drupal-backend**: Understanding of Drupal plugin system and interface patterns

## Acceptance Criteria
- [ ] File `src/Plugin/McpToolInterface.php` is created
- [ ] Interface declares `getAuthMetadata()` method returning ?array
- [ ] Interface declares `getAuthLevel()` method returning string
- [ ] Interface declares `requiresAuthentication()` method returning bool
- [ ] Interface declares `getRequiredScopes()` method returning array
- [ ] All methods have proper PHPDoc comments with return type documentation
- [ ] Code follows Drupal coding standards

Use your internal Todo tool to track these and keep on track.

## Technical Requirements
- **Namespace**: `Drupal\jsonrpc_mcp\Plugin`
- **Interface name**: `McpToolInterface`
- **Return types**: Nullable types and strict typing
- **Documentation**: Complete PHPDoc for each method including parameter and return descriptions

## Input Dependencies
- Task 1: Requires ScopeDefinitions class to reference in documentation

## Output Artifacts
- `src/Plugin/McpToolInterface.php` - Interface implemented by McpToolBase in task 3

<details>
<summary>Implementation Notes</summary>

### File Location
Create file at: `src/Plugin/McpToolInterface.php`

### Interface Structure
```php
<?php

declare(strict_types=1);

namespace Drupal\jsonrpc_mcp\Plugin;

/**
 * Interface for MCP tool plugins with authentication support.
 */
interface McpToolInterface {

  /**
   * Returns authentication metadata for this tool.
   *
   * The auth metadata structure matches the MCP specification:
   * - scopes: Array of OAuth scope strings required
   * - description: Human-readable auth requirement description
   * - level: Optional explicit auth level ('none', 'optional', 'required')
   *
   * @return array|null
   *   Authentication metadata array, or NULL if no auth required.
   */
  public function getAuthMetadata(): ?array;

  /**
   * Returns the inferred authentication level for this tool.
   *
   * Authentication level is inferred from metadata:
   * - If scopes present but level undefined: 'required'
   * - If no scopes and no level: 'none'
   * - Explicit level in metadata overrides inference
   *
   * @return string
   *   One of: 'none', 'optional', 'required'
   */
  public function getAuthLevel(): string;

  /**
   * Checks if this tool requires authentication.
   *
   * @return bool
   *   TRUE if authentication level is 'required', FALSE otherwise.
   */
  public function requiresAuthentication(): bool;

  /**
   * Returns the OAuth scopes required to invoke this tool.
   *
   * @return array
   *   Array of scope strings (e.g., ['content:read', 'user:write']).
   *   Empty array if no scopes required.
   */
  public function getRequiredScopes(): array;

}
```

### Design Rationale
- **Nullable return for getAuthMetadata()**: Tools without auth requirements return NULL
- **String return for getAuthLevel()**: Always returns a valid level (defaults to 'none')
- **Boolean requiresAuthentication()**: Convenience method for common check
- **Array return for getRequiredScopes()**: Always returns array (empty if none)

### Verification
After creating the file:
1. Run `vendor/bin/phpcs --standard=Drupal,DrupalPractice src/Plugin/McpToolInterface.php`
2. Verify interface methods match specification in drupal-changes.md
3. Check PHPDoc completeness for all methods
</details>
