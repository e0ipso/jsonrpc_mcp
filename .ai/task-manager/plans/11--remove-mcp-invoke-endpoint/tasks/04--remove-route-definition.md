---
id: 4
group: 'code-removal'
dependencies: [3]
status: 'pending'
created: '2025-10-28'
skills:
  - drupal-backend
  - yaml
---

# Remove Invoke Route Definition

## Objective

Remove the `jsonrpc_mcp.tools_invoke` route definition from the routing configuration file, leaving only the list and describe routes. Verify the route no longer exists after cache rebuild.

## Skills Required

- **drupal-backend**: Understand Drupal routing system
- **yaml**: Edit YAML configuration files

## Acceptance Criteria

- [ ] `jsonrpc_mcp.tools_invoke` route definition removed from routing.yml
- [ ] `tools_list` and `tools_describe` routes preserved
- [ ] File maintains valid YAML syntax
- [ ] Route returns 404 after cache rebuild: `curl -I /mcp/tools/invoke` returns 404
- [ ] List/describe routes still work: `curl /mcp/tools/list` returns 200

Use your internal Todo tool to track these and keep on track.

## Technical Requirements

**File**: `jsonrpc_mcp.routing.yml`

**Route to remove** (lines 21-31):

```yaml
jsonrpc_mcp.tools_invoke:
  path: '/mcp/tools/invoke'
  defaults:
    _controller: '\Drupal\jsonrpc_mcp\Controller\McpToolsController::invoke'
  requirements:
    # Requires base JSON-RPC permission to invoke tools.
    # Individual method permissions are enforced by JSON-RPC access system.
    _permission: 'use jsonrpc services'
  methods: [POST]
  options:
    _auth: ['oauth2']
```

**Routes to preserve**:

- `jsonrpc_mcp.tools_list` (lines 1-10)
- `jsonrpc_mcp.tools_describe` (lines 12-19)

## Input Dependencies

- Task 3 (controller method removal) must complete first so the route doesn't reference a non-existent method

## Output Artifacts

- Updated jsonrpc_mcp.routing.yml with invoke route removed
- Verified 404 response for removed route
- Verified 200 responses for remaining routes

## Implementation Notes

<details>
<summary>Detailed implementation guidance</summary>

### Step 1: Read routing file

Read the current routing configuration:

```bash
cat jsonrpc_mcp.routing.yml
```

Confirm the exact line numbers for the invoke route definition.

### Step 2: Remove route definition

Use the Edit tool to remove lines 21-31 (the entire `jsonrpc_mcp.tools_invoke` route definition).

Preserve blank lines between the remaining two routes for readability.

### Step 3: Validate YAML syntax

After editing, validate the YAML syntax:

```bash
php -r "yaml_parse_file('jsonrpc_mcp.routing.yml');"
```

If this command returns without error, the YAML is valid.

### Step 4: Rebuild Drupal cache

The routing system caches route definitions, so rebuild the cache:

```bash
vendor/bin/drush cache:rebuild
```

### Step 5: Verify route removal

Test that the removed route returns 404:

```bash
curl -I https://drupal-contrib.ddev.site/mcp/tools/invoke
```

Expected response: `HTTP/1.1 404 Not Found`

### Step 6: Verify remaining routes work

Test the list route:

```bash
curl -I https://drupal-contrib.ddev.site/mcp/tools/list
```

Expected response: `HTTP/1.1 200 OK` (or 403 if not authenticated)

Test the describe route:

```bash
curl -I "https://drupal-contrib.ddev.site/mcp/tools/describe?name=examples.contentTypes.list"
```

Expected response: `HTTP/1.1 200 OK` (or 403 if not authenticated)

### Step 7: Verify no other references

Search the codebase for any references to the route name:

```bash
grep -r "jsonrpc_mcp.tools_invoke" .
```

Should return no results (except possibly in archived task files).

</details>
