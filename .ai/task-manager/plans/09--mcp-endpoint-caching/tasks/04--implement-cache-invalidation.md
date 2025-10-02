---
id: 4
group: 'cache-invalidation'
dependencies: [3]
status: 'completed'
created: '2025-10-02'
skills:
  - drupal-backend
  - php
---

# Implement cache tag invalidation for plugin definition changes

## Objective

Add cache tag invalidation logic that clears the `jsonrpc_mcp:discovery` cache tag when plugin definitions change (module install/uninstall, cache rebuild), ensuring cached discovery responses are refreshed appropriately.

## Skills Required

- **drupal-backend**: Understanding of Drupal hooks, cache invalidation, and module lifecycle
- **php**: Implementing event subscribers or hook implementations

## Acceptance Criteria

- [x] `jsonrpc_mcp:discovery` cache tag is invalidated on module install
- [x] `jsonrpc_mcp:discovery` cache tag is invalidated on module uninstall
- [x] `jsonrpc_mcp:discovery` cache tag is invalidated on cache rebuild
- [x] Service method `McpToolDiscoveryService::invalidateDiscoveryCache()` is implemented
- [x] Cache invalidation is triggered through proper Drupal APIs

## Technical Requirements

**Files to modify/create**:

1. `jsonrpc_mcp.module` - Add hook implementations
2. `src/Service/McpToolDiscoveryService.php` - Add invalidation method

**Implementation approach**:

- Use `hook_modules_installed()` and `hook_modules_uninstalled()`
- Implement service method for manual invalidation
- Leverage `\Drupal\Core\Cache\Cache::invalidateTags()`

## Input Dependencies

- Task 3: Routes must be cache-enabled before invalidation is meaningful
- Existing `McpToolDiscoveryService` class

## Output Artifacts

- Cache invalidation hooks in `jsonrpc_mcp.module`
- Public invalidation method in `McpToolDiscoveryService`
- Automatic cache clearing on plugin system changes

## Implementation Notes

<details>
<summary>Detailed implementation steps</summary>

1. **Add invalidation method to `McpToolDiscoveryService`**:

   Open `src/Service/McpToolDiscoveryService.php` and add:

   ```php
   /**
    * Invalidates the MCP tool discovery cache.
    *
    * This method clears all cached discovery responses by invalidating
    * the custom cache tag. Call this when plugin definitions change or
    * when manual cache clearing is needed.
    */
   public function invalidateDiscoveryCache(): void {
     \Drupal\Core\Cache\Cache::invalidateTags(['jsonrpc_mcp:discovery']);
   }
   ```

2. **Create or update `jsonrpc_mcp.module`**:

   If the file doesn't exist, create it at the module root. Add these hooks:

   ```php
   <?php

   /**
    * @file
    * Primary module hooks for jsonrpc_mcp module.
    */

   use Drupal\Core\Cache\Cache;

   /**
    * Implements hook_modules_installed().
    *
    * Invalidates MCP discovery cache when modules are installed, as new
    * JSON-RPC methods with MCP tools may be introduced.
    */
   function jsonrpc_mcp_modules_installed($modules) {
     // Invalidate discovery cache to pick up any new MCP tools from installed modules.
     Cache::invalidateTags(['jsonrpc_mcp:discovery']);
   }

   /**
    * Implements hook_modules_uninstalled().
    *
    * Invalidates MCP discovery cache when modules are uninstalled, as
    * JSON-RPC methods with MCP tools may be removed.
    */
   function jsonrpc_mcp_modules_uninstalled($modules) {
     // Invalidate discovery cache to remove any MCP tools from uninstalled modules.
     Cache::invalidateTags(['jsonrpc_mcp:discovery']);
   }
   ```

3. **Verify cache rebuild integration**:

   The `jsonrpc_mcp:discovery` cache tag will be automatically invalidated during
   `drush cache:rebuild` because Drupal's cache rebuild clears all cache tags.
   No additional code needed for this scenario.

4. **Add docblock to explain the cache tag**:

   In `McpToolsController.php`, add a class-level comment documenting the cache tag:

   ```php
   /**
    * Controller for MCP tools discovery endpoint.
    *
    * This controller handles the /mcp/tools/list HTTP endpoint, providing
    * MCP-compliant tool discovery with cursor-based pagination. It coordinates
    * the McpToolDiscoveryService and McpToolNormalizer to return JSON-RPC
    * methods marked with the #[McpTool] attribute in MCP tool schema format.
    *
    * Cache tags used:
    * - jsonrpc_mcp:discovery: Invalidated when modules install/uninstall or
    *   when plugin definitions change.
    * - user.permissions: Automatically invalidated when permission system changes.
    */
   ```

5. **Test the invalidation**:
   - Install a module with JSON-RPC methods → cache should invalidate
   - Uninstall a module → cache should invalidate
   - Run `drush cache:rebuild` → cache should clear
   - Call `invalidateDiscoveryCache()` programmatically → cache should clear

**Important notes**:

- Don't invalidate on every cache clear operation - only on plugin-relevant changes
- The `user.permissions` cache tag is handled automatically by Drupal core
- Cache invalidation is inexpensive - it just marks cache entries as invalid
- The actual cache rebuild happens on the next request (lazy regeneration)
</details>
