---
id: 1
group: 'oauth-scope-system'
dependencies: []
status: 'completed'
created: '2025-10-19'
skills:
  - php
  - drupal-backend
---

# Create OAuth Scope Definition System

## Objective

Create a centralized OAuth scope management system that defines, validates, and provides metadata for all OAuth scopes used in MCP tool authentication.

## Skills Required

- **php**: Implementation of static utility class with PHP 8.1+
- **drupal-backend**: Understanding of Drupal module structure and naming conventions

## Acceptance Criteria

- [ ] File `src/OAuth/ScopeDefinitions.php` is created with proper namespace
- [ ] Class provides `getScopes()` method returning array of scope definitions
- [ ] Class provides `isValid($scope)` method for scope validation
- [ ] Class provides `getScopeInfo($scope)` method for scope metadata retrieval
- [ ] All 8 required scopes are defined: profile, content:read, content:write, content:delete, content_type:read, user:read, user:write, admin:access
- [ ] Each scope has label and description fields
- [ ] Code follows Drupal coding standards (PHPCS compliant)

Use your internal Todo tool to track these and keep on track.

## Technical Requirements

- **Namespace**: `Drupal\jsonrpc_mcp\OAuth`
- **Class name**: `ScopeDefinitions`
- **Methods**: Static methods only (utility class pattern)
- **Return types**: Strict typing with PHP 8.1+ syntax
- **Documentation**: Full PHPDoc comments for class and methods

## Input Dependencies

None - This is the foundation component with no dependencies.

## Output Artifacts

- `src/OAuth/ScopeDefinitions.php` - Used by middleware, install hooks, and plugin base classes

<details>
<summary>Implementation Notes</summary>

### File Location

Create file at: `src/OAuth/ScopeDefinitions.php`

### Class Structure

```php
<?php

declare(strict_types=1);

namespace Drupal\jsonrpc_mcp\OAuth;

/**
 * Defines available OAuth scopes for MCP tools.
 */
class ScopeDefinitions {

  /**
   * Returns all defined scopes.
   *
   * @return array
   *   Array of scope definitions keyed by scope name.
   */
  public static function getScopes(): array {
    return [
      'profile' => [
        'label' => 'User Profile',
        'description' => 'Access to user profile information',
      ],
      'content:read' => [
        'label' => 'Read Content',
        'description' => 'Read access to published content',
      ],
      'content:write' => [
        'label' => 'Write Content',
        'description' => 'Create and update content',
      ],
      'content:delete' => [
        'label' => 'Delete Content',
        'description' => 'Delete content',
      ],
      'content_type:read' => [
        'label' => 'Read Content Types',
        'description' => 'Access to content type definitions and configuration',
      ],
      'user:read' => [
        'label' => 'Read Users',
        'description' => 'Read user account information',
      ],
      'user:write' => [
        'label' => 'Write Users',
        'description' => 'Create and update user accounts',
      ],
      'admin:access' => [
        'label' => 'Administrative Access',
        'description' => 'Full administrative access to all content and configuration',
      ],
    ];
  }

  /**
   * Validates if a scope exists.
   *
   * @param string $scope
   *   The scope to validate.
   *
   * @return bool
   *   TRUE if scope is valid.
   */
  public static function isValid(string $scope): bool {
    return isset(self::getScopes()[$scope]);
  }

  /**
   * Returns scope information.
   *
   * @param string $scope
   *   The scope name.
   *
   * @return array|null
   *   Scope definition or NULL if not found.
   */
  public static function getScopeInfo(string $scope): ?array {
    $scopes = self::getScopes();
    return $scopes[$scope] ?? NULL;
  }

}
```

### Scope Design Principles

- Use colon-separated namespacing (e.g., `content:read`)
- Keep scope names lowercase
- Each scope represents a distinct permission level
- Scopes are additive (multiple scopes grant combined permissions)

### Verification

After creating the file, verify:

1. Run `vendor/bin/phpcs --standard=Drupal,DrupalPractice src/OAuth/ScopeDefinitions.php`
2. Check namespace and class name match file location
3. Verify all 8 scopes are present with correct structure
</details>
