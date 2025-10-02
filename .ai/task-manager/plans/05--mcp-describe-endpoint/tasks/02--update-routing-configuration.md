---
id: 2
group: 'routing-configuration'
dependencies: [1]
status: 'pending'
created: '2025-10-02'
skills:
  - drupal-backend
---

# Update Routing Configuration for MCP Endpoints

## Objective

Update `jsonrpc_mcp.routing.yml` to add two new routes (`/mcp/tools/describe` and `/mcp/tools/invoke`) and update the existing list route to require the new "access mcp tool discovery" permission.

## Skills Required

- **drupal-backend**: Knowledge of Drupal routing system, HTTP method restrictions, and permission-based access control

## Acceptance Criteria

- [ ] Existing `jsonrpc_mcp.tools_list` route updated to use `_permission: 'access mcp tool discovery'`
- [ ] New `jsonrpc_mcp.tools_describe` route added with GET method and permission requirement
- [ ] New `jsonrpc_mcp.tools_invoke` route added with POST method and public access
- [ ] All routes have `no_cache: 'TRUE'` option for dynamic content
- [ ] Route definitions follow Drupal routing conventions
- [ ] Routes are accessible after cache rebuild

Use your internal Todo tool to track these and keep on track.

## Technical Requirements

Update the existing `jsonrpc_mcp.routing.yml` file to include all three routes:

**Updated list route:**

- Path: `/mcp/tools/list`
- Controller: `\Drupal\jsonrpc_mcp\Controller\McpToolsController::list`
- Access: `_permission: 'access mcp tool discovery'` (changed from `_access: 'TRUE'`)
- Options: `no_cache: 'TRUE'`

**New describe route:**

- Path: `/mcp/tools/describe`
- Controller: `\Drupal\jsonrpc_mcp\Controller\McpToolsController::describe`
- Access: `_permission: 'access mcp tool discovery'`
- Options: `no_cache: 'TRUE'`

**New invoke route:**

- Path: `/mcp/tools/invoke`
- Controller: `\Drupal\jsonrpc_mcp\Controller\McpToolsController::invoke`
- Methods: POST (explicit via route requirements)
- Access: `_access: 'TRUE'` (public - access control delegated to JSON-RPC handler)
- Options: `no_cache: 'TRUE'`

## Input Dependencies

- Task 1: The "access mcp tool discovery" permission must be defined before it can be referenced in routing

## Output Artifacts

- Updated `jsonrpc_mcp.routing.yml` with all three route definitions

## Implementation Notes

<details>
<summary>Detailed Implementation Steps</summary>

1. **Read existing routing file**:
   - Location: `/var/www/html/web/modules/contrib/jsonrpc_mcp/jsonrpc_mcp.routing.yml`
   - Review current list route configuration

2. **Update list route**:
   - Change `_access: 'TRUE'` to `_permission: 'access mcp tool discovery'` under requirements
   - Keep all other configuration unchanged
   - Update comment to reflect permission requirement

3. **Add describe route**:

   ```yaml
   jsonrpc_mcp.tools_describe:
     path: '/mcp/tools/describe'
     defaults:
       _controller: '\Drupal\jsonrpc_mcp\Controller\McpToolsController::describe'
       _title: 'MCP Tool Description'
     requirements:
       _permission: 'access mcp tool discovery'
     options:
       no_cache: 'TRUE'
   ```

4. **Add invoke route**:

   ```yaml
   jsonrpc_mcp.tools_invoke:
     path: '/mcp/tools/invoke'
     defaults:
       _controller: '\Drupal\jsonrpc_mcp\Controller\McpToolsController::invoke'
       _title: 'MCP Tool Invocation'
     requirements:
       _access: 'TRUE'
       _method: 'POST'
     options:
       no_cache: 'TRUE'
   ```

5. **Access control rationale**:
   - **List & Describe**: Require permission because these expose tool schemas
   - **Invoke**: Public at routing level because JSON-RPC handler enforces method-specific permissions
   - This allows fine-grained execution control while managing discovery separately

6. **HTTP method restriction**:
   - Invoke route uses `_method: 'POST'` to restrict to POST requests only
   - Describe and list routes default to GET (can omit explicit method declaration)

7. **Testing routes**:
   - Run `vendor/bin/drush cache:rebuild` after making changes
   - Use `vendor/bin/drush route` to list all routes and verify new ones appear
   - Routes: `jsonrpc_mcp.tools_list`, `jsonrpc_mcp.tools_describe`, `jsonrpc_mcp.tools_invoke`

8. **Common pitfalls**:
   - Ensure proper YAML indentation (2 spaces)
   - Permission machine name must match exactly: `access mcp tool discovery` (with spaces)
   - Do not add trailing spaces
   - Ensure `no_cache` option is string `'TRUE'`, not boolean
   </details>
