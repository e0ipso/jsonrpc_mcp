---
id: 1
group: 'cleanup'
dependencies: []
status: 'completed'
created: 2025-11-03
skills:
  - drupal-backend
---

# Remove Old /mcp/tools/invoke Endpoint

## Objective

Remove the deprecated single invocation endpoint `/mcp/tools/invoke` from routing configuration and controller to establish clean slate for per-tool URL architecture.

## Skills Required

- **drupal-backend**: Drupal routing system, controller methods, and module configuration

## Acceptance Criteria

- [ ] Route `jsonrpc_mcp.tools_invoke` removed from `jsonrpc_mcp.routing.yml`
- [ ] Method `McpToolsController::invoke()` removed from controller
- [ ] All references to old endpoint removed from codebase
- [ ] Existing tests updated to not reference `/mcp/tools/invoke`

## Technical Requirements

Files to modify:
- `jsonrpc_mcp.routing.yml` - Remove `jsonrpc_mcp.tools_invoke` route definition
- `src/Controller/McpToolsController.php` - Remove `invoke()` method
- Update any test files that reference the old endpoint

Ensure the `list()` and `describe()` methods remain intact in `McpToolsController`.

## Input Dependencies

None - this is the first task establishing clean slate.

## Output Artifacts

- Modified `jsonrpc_mcp.routing.yml` without old invoke route
- Modified `McpToolsController.php` without invoke() method
- Updated test files (if any reference the endpoint)

<details>
<summary>Implementation Notes</summary>

1. **Remove route definition** from `jsonrpc_mcp.routing.yml`:
   - Delete the entire `jsonrpc_mcp.tools_invoke` route block
   - Keep `jsonrpc_mcp.tools_list` and `jsonrpc_mcp.tools_describe` routes intact

2. **Remove controller method** from `src/Controller/McpToolsController.php`:
   - Delete the `invoke()` method completely
   - Do NOT remove the class constructor or other methods (list, describe)

3. **Search for test references**:
   ```bash
   grep -r "tools/invoke" tests/
   ```
   - Update any tests that POST to `/mcp/tools/invoke`
   - Either remove those tests or mark them for future reimplementation with new per-tool URLs

4. **Verify no other references exist**:
   ```bash
   grep -r "tools_invoke\|tools/invoke" src/ tests/
   ```

**No backward compatibility is needed** per plan requirements.
</details>
