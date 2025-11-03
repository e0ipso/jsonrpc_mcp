---
id: 2
group: 'routing'
dependencies: [1]
status: 'completed'
created: 2025-11-03
skills:
  - drupal-backend
  - php
---

# Implement Dynamic Route Generation Service

## Objective

Create `McpToolRoutes` service that dynamically generates one route per discovered MCP tool using Drupal's route callback system.

## Skills Required

- **drupal-backend**: Drupal routing system, route callbacks, service container
- **php**: Object-oriented PHP, dependency injection

## Acceptance Criteria

- [ ] Class `src/Routing/McpToolRoutes.php` created with `routes()` static method
- [ ] Service `jsonrpc_mcp.routing.mcp_tool_routes` defined in `jsonrpc_mcp.services.yml`
- [ ] Route callback registered in `jsonrpc_mcp.routing.yml`
- [ ] Each discovered MCP tool gets a route at `/mcp/tools/{tool_name}`
- [ ] Routes include proper defaults (controller, tool_name) and requirements (methods, access)
- [ ] Routes regenerate on cache clear / module install

## Technical Requirements

**Create new file** `src/Routing/McpToolRoutes.php`:
- Static method `routes()` returning `RouteCollection`
- Inject `McpToolDiscoveryService` via constructor
- For each tool, generate `Route` object with:
  - Path: `/mcp/tools/{tool_id}` (tool_id = JSON-RPC method ID like "cache.rebuild")
  - Controller: `\Drupal\jsonrpc_mcp\Controller\McpToolInvokeController::invoke`
  - Defaults: `tool_name` parameter, `_title`
  - Requirements: `_method: 'GET|POST'`, conditional `_custom_access` if `auth.level === 'required'`
  - Options: `no_cache: TRUE`

**Update** `jsonrpc_mcp.routing.yml`:
```yaml
route_callbacks:
  - '\Drupal\jsonrpc_mcp\Routing\McpToolRoutes::routes'
```

**Update** `jsonrpc_mcp.services.yml`:
```yaml
jsonrpc_mcp.routing.mcp_tool_routes:
  class: Drupal\jsonrpc_mcp\Routing\McpToolRoutes
  arguments:
    - '@jsonrpc_mcp.tool_discovery'
```

## Input Dependencies

- Requires `McpToolDiscoveryService` from existing codebase
- Task 1 must be complete (old endpoint removed)

## Output Artifacts

- `src/Routing/McpToolRoutes.php` - Route generation service
- Updated `jsonrpc_mcp.routing.yml` with route callback
- Updated `jsonrpc_mcp.services.yml` with service definition
- Per-tool routes accessible at `/mcp/tools/{tool_name}`

<details>
<summary>Implementation Notes</summary>

**File: `src/Routing/McpToolRoutes.php`**

```php
<?php

declare(strict_types=1);

namespace Drupal\jsonrpc_mcp\Routing;

use Drupal\jsonrpc_mcp\Service\McpToolDiscoveryService;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Dynamically generates routes for MCP tools.
 */
class McpToolRoutes {

  /**
   * The tool discovery service.
   */
  protected McpToolDiscoveryService $toolDiscovery;

  /**
   * Constructs a new McpToolRoutes object.
   */
  public function __construct(McpToolDiscoveryService $toolDiscovery) {
    $this->toolDiscovery = $toolDiscovery;
  }

  /**
   * Returns dynamic routes for all discovered MCP tools.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   The route collection.
   */
  public function routes(): RouteCollection {
    $collection = new RouteCollection();
    $tools = $this->toolDiscovery->discoverTools();

    foreach ($tools as $tool_name => $tool_data) {
      // Convert dots to underscores for route name
      $route_name = 'jsonrpc_mcp.tool.' . str_replace('.', '_', $tool_name);

      // Determine if authentication is required
      $auth_level = $tool_data['annotations']['auth']['level'] ?? null;
      $requires_auth = $auth_level === 'required';

      $route = new Route(
        '/mcp/tools/' . $tool_name,
        [
          '_controller' => '\Drupal\jsonrpc_mcp\Controller\McpToolInvokeController::invoke',
          '_title' => 'Invoke ' . $tool_name,
          'tool_name' => $tool_name,
        ],
        [
          '_method' => 'GET|POST',
        ],
        [
          'no_cache' => TRUE,
        ]
      );

      // Add custom access check if authentication required
      if ($requires_auth) {
        $route->setRequirement('_custom_access', '\Drupal\jsonrpc_mcp\Access\McpToolAccessCheck::access');
      } else {
        $route->setRequirement('_access', 'TRUE');
      }

      $collection->add($route_name, $route);
    }

    return $collection;
  }

}
```

**Key Implementation Details**:

1. **Static vs Instance**: While the callback must be static, use a service pattern for testability
2. **Route Naming**: Convert dots to underscores (e.g., `cache.rebuild` → `jsonrpc_mcp.tool.cache_rebuild`)
3. **Path Encoding**: Tool names in paths should preserve dots (Drupal routing handles special chars)
4. **Access Logic**: Conditional `_custom_access` requirement only for tools with `auth.level === 'required'`
5. **Cache Control**: `no_cache: TRUE` prevents caching invocation responses

**Testing Route Generation**:
```bash
# Clear cache to trigger route rebuild
vendor/bin/drush cr

# List routes to verify per-tool routes exist
vendor/bin/drush route:debug | grep jsonrpc_mcp.tool
```

**Note**: The `McpToolInvokeController` doesn't exist yet - that's created in task 3. This task just generates the routes.
</details>
