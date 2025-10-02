---
id: 3
group: 'routing-configuration'
dependencies: [2]
status: 'pending'
created: '2025-10-02'
skills:
  - drupal-backend
---

# Remove no_cache restrictions from discovery endpoint routes

## Objective

Remove the `no_cache: 'TRUE'` option from discovery endpoint route definitions to allow Drupal's page cache to cache the responses. Keep the restriction for the invoke endpoint since it executes state-changing operations.

## Skills Required

- **drupal-backend**: Understanding of Drupal routing and cache configuration

## Acceptance Criteria

- [ ] `jsonrpc_mcp.tools_list` route no longer has `options: no_cache: 'TRUE'`
- [ ] `jsonrpc_mcp.tools_describe` route no longer has `options: no_cache: 'TRUE'`
- [ ] `jsonrpc_mcp.tools_invoke` route STILL has `options: no_cache: 'TRUE'` (unchanged)
- [ ] Route configuration is valid YAML syntax
- [ ] Routes are accessible after cache rebuild

## Technical Requirements

**File to modify**: `jsonrpc_mcp.routing.yml`

**Changes required**:

1. Remove the entire `options:` section from `jsonrpc_mcp.tools_list`
2. Remove the entire `options:` section from `jsonrpc_mcp.tools_describe`
3. Keep `options: no_cache: 'TRUE'` for `jsonrpc_mcp.tools_invoke`

## Input Dependencies

- Task 2: Cache metadata must be properly attached to responses before enabling caching

## Output Artifacts

- Updated `jsonrpc_mcp.routing.yml` with cache-enabled discovery routes
- Routes ready for page cache integration

## Implementation Notes

<details>
<summary>Detailed implementation steps</summary>

1. **Open** `jsonrpc_mcp.routing.yml`

2. **Modify `jsonrpc_mcp.tools_list` route**:

   ```yaml
   jsonrpc_mcp.tools_list:
     path: '/mcp/tools/list'
     defaults:
       _controller: '\Drupal\jsonrpc_mcp\Controller\McpToolsController::list'
       _title: 'MCP Tools Discovery'
     requirements:
       _permission: 'access mcp tool discovery'
     # REMOVE the options section:
     # options:
     #   no_cache: 'TRUE'
   ```

3. **Modify `jsonrpc_mcp.tools_describe` route**:

   ```yaml
   jsonrpc_mcp.tools_describe:
     path: '/mcp/tools/describe'
     defaults:
       _controller: '\Drupal\jsonrpc_mcp\Controller\McpToolsController::describe'
       _title: 'MCP Tool Description'
     requirements:
       _permission: 'access mcp tool discovery'
     # REMOVE the options section:
     # options:
     #   no_cache: 'TRUE'
   ```

4. **Keep `jsonrpc_mcp.tools_invoke` route unchanged**:

   ```yaml
   jsonrpc_mcp.tools_invoke:
     path: '/mcp/tools/invoke'
     defaults:
       _controller: '\Drupal\jsonrpc_mcp\Controller\McpToolsController::invoke'
       _title: 'MCP Tool Invocation'
     requirements:
       _permission: 'use jsonrpc services'
       _method: 'POST'
     options:
       no_cache: 'TRUE' # KEEP THIS - invocation should never be cached
   ```

5. **Validate YAML syntax** after changes:
   ```bash
   vendor/bin/drush cache:rebuild
   ```

**Important notes**:

- The `no_cache` option completely bypasses Drupal's page cache
- Removing it allows the cache metadata (from task 2) to control caching behavior
- The invoke endpoint MUST keep `no_cache: 'TRUE'` because it executes actions
- After this change, discovery endpoints will be cached according to their cache metadata
</details>
