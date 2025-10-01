---
id: 1
group: 'module-scaffolding'
dependencies: []
status: 'completed'
created: '2025-10-01'
skills: ['drupal-backend']
---

# Create jsonrpc_mcp_examples Submodule Structure

## Objective

Create the basic directory structure and module definition file for the jsonrpc_mcp_examples submodule, establishing the foundation for example MCP tools.

## Skills Required

- `drupal-backend`: Drupal module structure, info.yml syntax, dependency management

## Acceptance Criteria

- [ ] Directory structure created at `modules/jsonrpc_mcp_examples/`
- [ ] `jsonrpc_mcp_examples.info.yml` created with correct dependencies
- [ ] `src/Plugin/jsonrpc/Method/` directory exists
- [ ] `tests/src/Kernel/` directory exists
- [ ] `tests/src/Unit/` directory exists
- [ ] Module can be enabled with `drush pm:enable jsonrpc_mcp_examples`

## Technical Requirements

- Module must declare dependency on `jsonrpc:jsonrpc` (>= 3.0.0-beta1)
- Module must declare dependency on `drupal:node` (core module)
- Module must declare dependency on `jsonrpc_mcp:jsonrpc_mcp`
- Core version requirement: `^10.2 || ^11`
- Package: `Web services`
- Module type: `module`

## Input Dependencies

None - this is the foundation task.

## Output Artifacts

- `modules/jsonrpc_mcp_examples/jsonrpc_mcp_examples.info.yml`
- Directory structure for plugins and tests

<details>
<summary>Implementation Notes</summary>

### Module Info File Structure

Create `modules/jsonrpc_mcp_examples/jsonrpc_mcp_examples.info.yml`:

```yaml
name: JSON-RPC MCP Examples
description: Example MCP tools demonstrating JSON-RPC method patterns with MCP annotations
type: module
core_version_requirement: ^10.2 || ^11
package: Web services
dependencies:
  - jsonrpc:jsonrpc (>= 3.0.0-beta1)
  - drupal:node
  - jsonrpc_mcp:jsonrpc_mcp
```

### Directory Structure

Create these directories:

```
modules/jsonrpc_mcp_examples/
├── jsonrpc_mcp_examples.info.yml
├── src/
│   └── Plugin/
│       └── jsonrpc/
│           └── Method/
└── tests/
    └── src/
        ├── Kernel/
        └── Unit/
```

### Verification Steps

After creation:

1. Run `drush pm:enable jsonrpc_mcp_examples` to verify module can be enabled
2. Check for any dependency errors
3. Run `drush pm:uninstall jsonrpc_mcp_examples` to clean up

</details>
