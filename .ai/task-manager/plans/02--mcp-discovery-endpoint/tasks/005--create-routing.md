---
id: 5
group: 'mcp-discovery-endpoint'
dependencies: [3]
status: 'pending'
created: '2025-10-01'
skills: ['drupal-routing']
---

# Create Routing Configuration

## Objective

Define the `/mcp/tools/list` route in the routing configuration file, mapping it to the McpToolsController.

## Skills Required

- Drupal routing system (YAML route definition syntax, controller references)

## Acceptance Criteria

- [ ] File `jsonrpc_mcp.routing.yml` exists (or is updated if exists)
- [ ] Defines route `jsonrpc_mcp.tools_list`
- [ ] Path is `/mcp/tools/list`
- [ ] Controller is `\Drupal\jsonrpc_mcp\Controller\McpToolsController::list`
- [ ] Access requirement is `_access: 'TRUE'` (public endpoint)
- [ ] Includes `no_cache: 'TRUE'` option
- [ ] Clear cache after creation: `vendor/bin/drush cache:rebuild`

## Technical Requirements

**File Location:** `jsonrpc_mcp.routing.yml` (root of module)

**Route Definition:**

```yaml
jsonrpc_mcp.tools_list:
  path: '/mcp/tools/list'
  defaults:
    _controller: '\Drupal\jsonrpc_mcp\Controller\McpToolsController::list'
    _title: 'MCP Tools Discovery'
  requirements:
    _access: 'TRUE'
  options:
    no_cache: 'TRUE'
```

**Route Configuration Details:**

- **path**: URL path for the endpoint
- **\_controller**: Fully qualified class name and method
- **\_title**: Page title (for admin/debugging)
- **\_access: 'TRUE'**: Public access (MCP clients authenticate via standard Drupal auth)
- **no_cache: 'TRUE'**: Prevent page caching (tools may change)

## Input Dependencies

- Task 003 completed (McpToolsController exists)

## Output Artifacts

- `jsonrpc_mcp.routing.yml` - Route configuration
- Endpoint accessible at `/mcp/tools/list` after cache clear

## Implementation Notes

- If file already exists, add route to existing file
- Route names use dot notation: `module_name.route_name`
- Access control is handled by discovery service (filters methods by user permissions)
- Endpoint must not be cached to ensure fresh tool lists
- Verify route works: `curl https://drupal-site/mcp/tools/list | jq`
