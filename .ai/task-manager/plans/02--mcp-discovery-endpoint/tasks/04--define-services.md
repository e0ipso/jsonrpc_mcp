---
id: 4
group: 'mcp-discovery-endpoint'
dependencies: [1, 2]
status: 'completed'
created: '2025-10-01'
'skills: ['drupal-services']
---

# Define Services in jsonrpc_mcp.services.yml

## Objective

Create the service definitions file that registers McpToolDiscoveryService and McpToolNormalizer in Drupal's service container.

## Skills Required

- Drupal service container (YAML service definition syntax, argument references)

## Acceptance Criteria

- [ ] File `jsonrpc_mcp.services.yml` exists (or is updated if exists)
- [ ] Defines `jsonrpc_mcp.tool_discovery` service
- [ ] Discovery service arguments: `@jsonrpc.handler`, `@current_user`
- [ ] Defines `jsonrpc_mcp.tool_normalizer` service
- [ ] Normalizer has no constructor arguments
- [ ] Service definitions follow Drupal YAML syntax
- [ ] Clear cache after creation: `vendor/bin/drush cache:rebuild`

## Technical Requirements

**File Location:** `jsonrpc_mcp.services.yml` (root of module)

**Service Definitions:**

```yaml
services:
  jsonrpc_mcp.tool_discovery:
    class: Drupal\jsonrpc_mcp\Service\McpToolDiscoveryService
    arguments: ['@jsonrpc.handler', '@current_user']

  jsonrpc_mcp.tool_normalizer:
    class: Drupal\jsonrpc_mcp\Normalizer\McpToolNormalizer
```

**Service References:**

- `@jsonrpc.handler` - Provided by jsonrpc module
- `@current_user` - Core Drupal service for current user account

## Input Dependencies

- Task 001 completed (McpToolDiscoveryService class exists)
- Task 002 completed (McpToolNormalizer class exists)

## Output Artifacts

- `jsonrpc_mcp.services.yml` - Service container configuration
- Services available for dependency injection in controller (task 003)

## Implementation Notes

- If file already exists, add services to existing file
- Service names use dot notation: `module_name.service_name`
- Arguments are referenced with `@` prefix for service injection
- No tags or other options needed for these services
- Must clear cache after editing services.yml for changes to take effect
