---
id: 3
group: 'plugin-system'
dependencies: [2]
status: 'completed'
created: '2025-10-19'
skills:
  - php
  - drupal-backend
---

# Implement McpToolBase Authentication Methods

## Objective

Implement authentication-related methods in the McpToolBase abstract class with intelligent auth level inference and backward compatibility.

## Skills Required

- **php**: Implementation of interface methods with complex logic
- **drupal-backend**: Understanding of Drupal plugin base classes and attribute handling

## Acceptance Criteria

- [ ] File `src/Plugin/McpToolBase.php` is created or updated
- [ ] Class implements McpToolInterface
- [ ] Method `getAuthMetadata()` extracts auth from plugin definition annotations
- [ ] Method `getAuthLevel()` implements level inference logic correctly
- [ ] Method `requiresAuthentication()` returns correct boolean based on level
- [ ] Method `getRequiredScopes()` extracts scopes array from auth metadata
- [ ] Backward compatibility: tools without auth metadata default to 'none' level
- [ ] Code follows Drupal coding standards

Use your internal Todo tool to track these and keep on track.

## Technical Requirements

- **Namespace**: `Drupal\jsonrpc_mcp\Plugin`
- **Class name**: `McpToolBase`
- **Extends**: `Drupal\Component\Plugin\PluginBase`
- **Implements**: `McpToolInterface`
- **Logic**: Implement auth level inference as specified in plan

## Input Dependencies

- Task 2: Requires McpToolInterface to implement

## Output Artifacts

- `src/Plugin/McpToolBase.php` - Base class extended by all MCP tool plugins

<details>
<summary>Implementation Notes</summary>

### File Location

Create or update file at: `src/Plugin/McpToolBase.php`

### Class Implementation

```php
<?php

declare(strict_types=1);

namespace Drupal\jsonrpc_mcp\Plugin;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for MCP tool plugins.
 */
abstract class McpToolBase extends PluginBase implements McpToolInterface {

  /**
   * {@inheritdoc}
   */
  public function getAuthMetadata(): ?array {
    $definition = $this->getPluginDefinition();
    return $definition['annotations']['auth'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   *
   * Gets the authentication level with inference from scopes.
   * - If scopes present but level undefined: defaults to 'required'
   * - If no scopes and no level: defaults to 'none'
   * - Explicit level overrides inference
   */
  public function getAuthLevel(): string {
    $auth = $this->getAuthMetadata();

    // No auth metadata at all.
    if (!$auth) {
      return 'none';
    }

    // Explicit level overrides inference.
    if (isset($auth['level'])) {
      return $auth['level'];
    }

    // Infer from scopes: if scopes present, default to 'required'.
    if (!empty($auth['scopes'])) {
      return 'required';
    }

    // No scopes, no explicit level.
    return 'none';
  }

  /**
   * {@inheritdoc}
   */
  public function requiresAuthentication(): bool {
    return $this->getAuthLevel() === 'required';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredScopes(): array {
    $auth = $this->getAuthMetadata();
    return $auth['scopes'] ?? [];
  }

}
```

### Auth Level Inference Logic

The inference follows this priority:

1. **Explicit level**: If `auth['level']` is set, use it (highest priority)
2. **Scope-based inference**: If scopes array exists and non-empty, infer 'required'
3. **Default to 'none'**: If no auth metadata or no scopes, default to 'none'

### Backward Compatibility

- Tools without `#[McpTool]` attribute: Return NULL from getAuthMetadata(), level = 'none'
- Tools with `#[McpTool]` but no auth annotation: Return NULL, level = 'none'
- This ensures existing tools continue working without modification

### Plugin Definition Structure

The auth metadata is accessed via:

```php
$definition['annotations']['auth'] = [
  'scopes' => ['content:read'],
  'description' => 'Requires read access',
  // 'level' => 'required',  // Optional, inferred if omitted
];
```

### Verification

After implementation:

1. Run `vendor/bin/phpcs --standard=Drupal,DrupalPractice src/Plugin/McpToolBase.php`
2. Test inference logic manually with different auth metadata configurations
3. Verify backward compatibility with tools without auth metadata
</details>
