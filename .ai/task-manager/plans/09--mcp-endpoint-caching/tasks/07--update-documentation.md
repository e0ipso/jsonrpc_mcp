---
id: 7
group: 'documentation'
dependencies: [4, 6]
status: 'pending'
created: '2025-10-02'
skills:
  - technical-writing
---

# Update module documentation with caching strategy details

## Objective

Document the caching implementation, cache tag usage, invalidation triggers, and performance implications in the module's documentation and code comments to ensure maintainability and clarity for future developers.

## Skills Required

- **technical-writing**: Ability to write clear, accurate technical documentation

## Acceptance Criteria

- [ ] Cache tag `jsonrpc_mcp:discovery` is documented in code comments
- [ ] Invalidation triggers are documented in `jsonrpc_mcp.module`
- [ ] AGENTS.md includes caching information in relevant sections
- [ ] Controller class has updated docblocks explaining cache behavior
- [ ] Service class documents the `invalidateDiscoveryCache()` method
- [ ] All documentation follows Drupal documentation standards

## Technical Requirements

**Files to update**:

1. `src/Controller/McpToolsController.php` - Class and method docblocks
2. `src/Service/McpToolDiscoveryService.php` - Service method documentation
3. `jsonrpc_mcp.module` - Hook documentation
4. `AGENTS.md` - Architecture and caching sections

**Documentation standards**: Follow Drupal API documentation conventions

## Input Dependencies

- Task 4: Cache invalidation implementation provides content to document
- Task 6: Test implementation validates documented behavior

## Output Artifacts

- Comprehensive documentation of caching strategy
- Clear guidance for future maintainers
- Updated architecture documentation

## Implementation Notes

<details>
<summary>Detailed implementation steps</summary>

### 1. Update McpToolsController Class Docblock

In `src/Controller/McpToolsController.php`, update the class docblock:

```php
/**
 * Controller for MCP tools discovery endpoint.
 *
 * This controller handles the /mcp/tools/list, /mcp/tools/describe, and
 * /mcp/tools/invoke HTTP endpoints, providing MCP-compliant tool discovery
 * with cursor-based pagination and JSON-RPC execution.
 *
 * ## Caching Strategy
 *
 * Discovery endpoints (/mcp/tools/list, /mcp/tools/describe) use permanent
 * caching with the following metadata:
 *
 * - **Cache Tags**:
 *   - `jsonrpc_mcp:discovery`: Custom tag invalidated when modules are
 *     installed/uninstalled or when manually cleared. Use
 *     McpToolDiscoveryService::invalidateDiscoveryCache() for programmatic
 *     invalidation.
 *   - `user.permissions`: Automatically invalidated when permission system
 *     changes (managed by Drupal core).
 *
 * - **Cache Contexts**:
 *   - `user`: Responses vary by user due to permission-based filtering.
 *   - `url.query_args:cursor`: Separate cache entries per pagination cursor.
 *   - `url.query_args:name`: Separate cache entries per tool name (describe).
 *
 * - **Max-Age**:
 *   - Discovery endpoints: Cache::PERMANENT (until explicitly invalidated)
 *   - Invoke endpoint: 0 (never cached, executes state-changing operations)
 *   - Error responses: 0 (not cached)
 *
 * ## Cache Invalidation
 *
 * The `jsonrpc_mcp:discovery` cache tag is invalidated:
 * - When modules are installed (hook_modules_installed)
 * - When modules are uninstalled (hook_modules_uninstalled)
 * - During cache rebuild (drush cache:rebuild)
 * - Via McpToolDiscoveryService::invalidateDiscoveryCache()
 *
 * @see \Drupal\jsonrpc_mcp\Service\McpToolDiscoveryService::invalidateDiscoveryCache()
 */
```

### 2. Update Method Docblocks

Add cache information to method docblocks:

```php
/**
 * Returns MCP-compliant tool list.
 *
 * Handles the /mcp/tools/list endpoint, returning a paginated list of
 * tools in MCP-compliant format. Pagination uses cursor-based approach
 * with base64-encoded offsets.
 *
 * This endpoint uses permanent caching with cache contexts for user and
 * pagination cursor. See class documentation for full caching details.
 *
 * @param \Symfony\Component\HttpFoundation\Request $request
 *   The HTTP request object.
 *
 * @return \Drupal\Core\Cache\CacheableJsonResponse
 *   JSON response with 'tools' array and 'nextCursor' field, including
 *   cache metadata for permanent caching.
 */
public function list(Request $request): CacheableJsonResponse {
```

### 3. Update McpToolDiscoveryService Documentation

In `src/Service/McpToolDiscoveryService.php`, document the invalidation method:

```php
/**
 * Invalidates the MCP tool discovery cache.
 *
 * This method clears all cached discovery responses by invalidating
 * the `jsonrpc_mcp:discovery` cache tag. Call this when plugin definitions
 * change or when manual cache clearing is needed outside of the standard
 * module lifecycle (install/uninstall).
 *
 * The cache tag is automatically invalidated by:
 * - hook_modules_installed() - When modules are installed
 * - hook_modules_uninstalled() - When modules are uninstalled
 * - drush cache:rebuild - During full cache rebuilds
 *
 * Use this method for programmatic cache clearing in other scenarios, such as:
 * - After programmatically registering new JSON-RPC methods
 * - When configuration changes affect tool availability
 * - During custom plugin definition updates
 *
 * @see jsonrpc_mcp_modules_installed()
 * @see jsonrpc_mcp_modules_uninstalled()
 */
public function invalidateDiscoveryCache(): void {
  \Drupal\Core\Cache\Cache::invalidateTags(['jsonrpc_mcp:discovery']);
}
```

### 4. Update Hook Documentation

In `jsonrpc_mcp.module`, ensure hooks are well-documented (should already be done in task 4, verify completeness).

### 5. Update AGENTS.md

Add a new section or update existing sections in `AGENTS.md`:

```markdown
## Caching Implementation

### Discovery Endpoint Caching

The MCP discovery endpoints (`/mcp/tools/list` and `/mcp/tools/describe`) implement
permanent caching using Drupal's `CacheableJsonResponse` and cache metadata API.

**Cache Strategy:**

- **Max-Age**: `Cache::PERMANENT` (cached until explicitly invalidated)
- **Cache Tags**: `jsonrpc_mcp:discovery`, `user.permissions`
- **Cache Contexts**: `user`, `url.query_args:cursor` (list), `url.query_args:name` (describe)

**Cache Invalidation:**
The `jsonrpc_mcp:discovery` cache tag is invalidated when:

1. Modules are installed or uninstalled (hooks in `jsonrpc_mcp.module`)
2. Cache is manually rebuilt (`drush cache:rebuild`)
3. `McpToolDiscoveryService::invalidateDiscoveryCache()` is called programmatically

**Performance Impact:**

- First request: Full plugin discovery and normalization
- Subsequent requests: Served from page cache (no PHP execution)
- Cache invalidation: Lazy regeneration on next request

**Important Notes:**

- The `/mcp/tools/invoke` endpoint is never cached (executes state-changing operations)
- Error responses (4xx, 5xx) are not cached
- Per-user caching ensures permission-based tool filtering works correctly
```

### 6. Verify Documentation Standards

Ensure all documentation:

- Uses proper Drupal docblock format
- Includes `@see` references to related code
- Explains WHY decisions were made, not just WHAT
- Provides examples where helpful
- Follows line length limits (80 characters for code comments)

**Important notes**:

- Documentation should explain the caching strategy and trade-offs
- Include cross-references between related documentation sections
- Document both automatic and manual invalidation methods
- Explain performance implications for users and developers
</details>
