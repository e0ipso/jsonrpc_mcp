---
id: 7
group: "oauth-scope-system"
dependencies: [1]
status: "completed"
created: "2025-10-19"
skills:
  - php
  - drupal-backend
---
# Implement Simple OAuth Integration

## Objective
Create module installation hook to register OAuth scope definitions with the Simple OAuth module, enabling scope assignment to OAuth consumers.

## Skills Required
- **php**: Implementation of Drupal hooks
- **drupal-backend**: Understanding of Drupal entity API, install hooks, and Simple OAuth integration

## Acceptance Criteria
- [ ] File `jsonrpc_mcp.install` is created
- [ ] `hook_install()` is implemented
- [ ] Hook loads scopes from `ScopeDefinitions::getScopes()`
- [ ] Hook creates `consumer_scope` entities for each scope
- [ ] Duplicate checking prevents re-creating existing scopes
- [ ] Each scope entity has name, label, and description fields
- [ ] Code follows Drupal coding standards
- [ ] Installation succeeds without errors

Use your internal Todo tool to track these and keep on track.

## Technical Requirements
- **File**: `jsonrpc_mcp.install` (module root)
- **Hook**: `hook_install()`
- **Entity type**: `consumer_scope` (from Simple OAuth module)
- **Storage**: Use entity type manager to access storage
- **Fields**: name (scope ID), label, description

## Input Dependencies
- Task 1: Requires ScopeDefinitions class with getScopes() method

## Output Artifacts
- `jsonrpc_mcp.install` - Executed during module installation to create scope entities

<details>
<summary>Implementation Notes</summary>

### File Location
Create file at module root: `jsonrpc_mcp.install`

### Install Hook Implementation
```php
<?php

/**
 * @file
 * Install, update and uninstall functions for the jsonrpc_mcp module.
 */

use Drupal\jsonrpc_mcp\OAuth\ScopeDefinitions;

/**
 * Implements hook_install().
 */
function jsonrpc_mcp_install() {
  // Create OAuth scopes.
  $entity_type_manager = \Drupal::entityTypeManager();
  $scope_storage = $entity_type_manager->getStorage('consumer_scope');

  foreach (ScopeDefinitions::getScopes() as $scope_id => $scope_info) {
    // Check if scope already exists.
    $existing = $scope_storage->loadByProperties(['name' => $scope_id]);
    if (empty($existing)) {
      $scope_storage->create([
        'name' => $scope_id,
        'description' => $scope_info['description'],
        'label' => $scope_info['label'],
      ])->save();
    }
  }
}
```

### Implementation Details

**Duplicate Prevention**:
- Use `loadByProperties(['name' => $scope_id])` to check for existing scope
- Only create if `$existing` array is empty
- This allows re-running install without creating duplicates

**Entity Structure**:
The `consumer_scope` entity from Simple OAuth expects:
- `name`: Unique scope identifier (e.g., 'content:read')
- `label`: Human-readable label
- `description`: Detailed description of scope permissions

**Error Handling**:
- If Simple OAuth is not installed, entity type won't exist
- Drupal will throw exception during installation
- Document Simple OAuth as required dependency in composer.json

### Update Hook Consideration
If scopes are added in future updates, create `hook_update_N()`:

```php
/**
 * Adds new OAuth scopes to Simple OAuth.
 */
function jsonrpc_mcp_update_9001() {
  // Same logic as hook_install() but called on module update
  // This ensures new scopes are added when module is updated
}
```

However, don't create this now unless specifically requested.

### Verification
After implementation:
1. Run `vendor/bin/phpcs --standard=Drupal,DrupalPractice jsonrpc_mcp.install`
2. Install module fresh: `vendor/bin/drush pm:install jsonrpc_mcp`
3. Check scope entities exist: `vendor/bin/drush php:eval "print_r(\Drupal::entityTypeManager()->getStorage('consumer_scope')->loadByProperties(['name' => 'content:read']));"`
4. Verify all 8 scopes are created
5. Try re-installing to verify duplicate prevention works
</details>
