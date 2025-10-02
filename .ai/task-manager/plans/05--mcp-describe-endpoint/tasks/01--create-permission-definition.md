---
id: 1
group: 'permission-system'
dependencies: []
status: 'completed'
created: '2025-10-02'
skills:
  - drupal-backend
---

# Create MCP Tool Discovery Permission Definition

## Objective

Create a new Drupal permission "access mcp tool discovery" that controls access to the `/mcp/tools/list` and `/mcp/tools/describe` endpoints, separating discovery permissions from tool execution permissions.

## Skills Required

- **drupal-backend**: Understanding of Drupal's permission system and YAML configuration files

## Acceptance Criteria

- [ ] `jsonrpc_mcp.permissions.yml` file created in module root
- [ ] Permission key is `access mcp tool discovery`
- [ ] Permission has clear title and description explaining its purpose
- [ ] Description mentions both `/mcp/tools/list` and `/mcp/tools/describe` endpoints
- [ ] Permission is discoverable in Drupal's permission administration UI after cache rebuild

Use your internal Todo tool to track these and keep on track.

## Technical Requirements

Create `jsonrpc_mcp.permissions.yml` in the module root directory with the following structure:

```yaml
access mcp tool discovery:
  title: 'Access MCP tool discovery'
  description: 'Allows users to view available MCP tools and their schemas via /mcp/tools/list and /mcp/tools/describe endpoints.'
```

The permission follows Drupal's standard permissions.yml format and will be automatically discovered by Drupal's permission system.

## Input Dependencies

None - this is a foundational task.

## Output Artifacts

- `jsonrpc_mcp.permissions.yml` file containing the permission definition

## Implementation Notes

<details>
<summary>Detailed Implementation Steps</summary>

1. **Create the permissions file**:
   - File location: `/var/www/html/web/modules/contrib/jsonrpc_mcp/jsonrpc_mcp.permissions.yml`
   - Use the exact YAML structure provided in Technical Requirements

2. **Permission naming convention**:
   - Permission machine name uses spaces, not underscores: `access mcp tool discovery`
   - Title should be human-readable and action-oriented
   - Description should clearly explain what granting this permission allows

3. **Testing the permission**:
   - After creating the file, run `vendor/bin/drush cache:rebuild`
   - Navigate to `/admin/people/permissions` in Drupal UI to verify permission appears
   - Verify permission appears under "jsonrpc_mcp module" section

4. **Access control philosophy**:
   - This permission controls **discovery** only (seeing what tools exist)
   - Tool **execution** remains controlled by JSON-RPC method-level permissions
   - This separation allows admins to grant broad discovery while restricting execution

5. **Common pitfalls**:
   - Do not use underscores in the permission machine name (use spaces)
   - Ensure proper YAML indentation (2 spaces for nested keys)
   - Do not add trailing spaces or extra newlines
   </details>
