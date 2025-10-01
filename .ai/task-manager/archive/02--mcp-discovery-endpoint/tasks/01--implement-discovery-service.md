---
id: 1
group: 'mcp-discovery-endpoint'
dependencies: []
status: 'completed'
created: '2025-10-01'
skills: ['drupal-service-architecture', 'php-reflection-api']
---

# Implement McpToolDiscoveryService

## Objective

Create the service that discovers JSON-RPC methods marked with both `#[JsonRpcMethod]` and `#[McpTool]` attributes, applying access control filtering.

## Skills Required

- Drupal service architecture (dependency injection, service definitions)
- PHP Reflection API (reading attributes from classes)
- Drupal access control (checking user permissions)

## Acceptance Criteria

- [ ] File `src/Service/McpToolDiscoveryService.php` exists with correct namespace
- [ ] Service accepts `HandlerInterface` and `AccountProxyInterface` via constructor
- [ ] Implements `discoverTools()` method returning array of MethodInterface
- [ ] Filters methods to only those with `#[McpTool]` attribute
- [ ] Applies access control using method's access() check
- [ ] Returns only methods the current user can access
- [ ] Uses reflection to detect McpTool attribute on plugin class

## Technical Requirements

**File Location:** `src/Service/McpToolDiscoveryService.php`

**Class Structure:**

```php
namespace Drupal\jsonrpc_mcp\Service;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jsonrpc\HandlerInterface;
use Drupal\jsonrpc\MethodInterface;
use Drupal\jsonrpc_mcp\Attribute\McpTool;

class McpToolDiscoveryService {

  public function __construct(
    protected HandlerInterface $handler,
    protected AccountProxyInterface $currentUser,
  ) {}

  /**
   * Discovers all accessible JSON-RPC methods marked as MCP tools.
   *
   * @return \Drupal\jsonrpc\MethodInterface[]
   *   Array of method interfaces with McpTool attribute.
   */
  public function discoverTools(): array {
    $all_methods = $this->handler->supportedMethods();
    $mcp_tools = [];

    foreach ($all_methods as $method_id => $method) {
      // Check if method has McpTool attribute
      if (!$this->hasMcpToolAttribute($method)) {
        continue;
      }

      // Check access permissions
      if (!$method->access('execute', $this->currentUser, FALSE)) {
        continue;
      }

      $mcp_tools[$method_id] = $method;
    }

    return $mcp_tools;
  }

  /**
   * Checks if a method has the McpTool attribute.
   */
  protected function hasMcpToolAttribute(MethodInterface $method): bool {
    // Use reflection to check for McpTool attribute on the plugin class
    $reflection = new \ReflectionClass($method->getPluginDefinition()['class']);
    $attributes = $reflection->getAttributes(McpTool::class);
    return !empty($attributes);
  }
}
```

**Integration Points:**

- Uses `jsonrpc.handler` service to get all JSON-RPC methods
- Uses `current_user` service for access control
- Returns MethodInterface objects for use by normalizer

## Input Dependencies

- Plan 01 completed (McpTool attribute exists)
- jsonrpc module installed and configured

## Output Artifacts

- `src/Service/McpToolDiscoveryService.php` - Working discovery service
- Service will be defined in task 004 (services.yml)

## Implementation Notes

- The service should be stateless - no internal caching at this stage
- Access control is delegated to the method's own access() implementation
- Reflection is used on every call - performance optimization can come later
- The `getPluginDefinition()` method returns the plugin definition array which includes the 'class' key
