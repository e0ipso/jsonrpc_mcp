---
id: 4
group: 'controller-implementation'
dependencies: [2]
status: 'completed'
created: '2025-10-02'
skills:
  - drupal-backend
---

# Inject JSON-RPC Handler Dependency into Controller

## Objective

Update `McpToolsController` constructor and service definition to inject the `HandlerInterface` from the jsonrpc module, enabling the invoke endpoint to execute JSON-RPC methods.

## Skills Required

- **drupal-backend**: Understanding of Drupal dependency injection, service containers, and service YAML configuration

## Acceptance Criteria

- [ ] `HandlerInterface` added to controller constructor parameters
- [ ] Constructor uses property promotion to store handler as protected property
- [ ] `create()` method updated to inject handler service from container
- [ ] Service definition in `jsonrpc_mcp.services.yml` updated with handler argument
- [ ] No breaking changes to existing constructor signature (only adds new parameter)
- [ ] PHPStan type annotations correct for handler property

Use your internal Todo tool to track these and keep on track.

## Technical Requirements

**Updated Constructor:**

```php
public function __construct(
  protected McpToolDiscoveryService $toolDiscovery,
  protected McpToolNormalizer $normalizer,
  protected HandlerInterface $handler,
) {}
```

**Updated create() Method:**

```php
public static function create(ContainerInterface $container) {
  // @phpstan-ignore-next-line return.type
  return new static(
    $container->get('jsonrpc_mcp.tool_discovery'),
    $container->get('jsonrpc_mcp.tool_normalizer'),
    $container->get('jsonrpc.handler'),
  );
}
```

**Service Definition (jsonrpc_mcp.services.yml):**

```yaml
services:
  jsonrpc_mcp.controller.mcp_tools:
    class: Drupal\jsonrpc_mcp\Controller\McpToolsController
    arguments:
      - '@jsonrpc_mcp.tool_discovery'
      - '@jsonrpc_mcp.tool_normalizer'
      - '@jsonrpc.handler'
```

## Input Dependencies

- Task 2: Routes must be defined before controller dependency changes
- jsonrpc module's `HandlerInterface` (provided by jsonrpc module dependency)

## Output Artifacts

- Updated `src/Controller/McpToolsController.php` with handler dependency
- Updated `jsonrpc_mcp.services.yml` (if it exists, otherwise note that controller uses static create())

## Implementation Notes

<details>
<summary>Detailed Implementation Steps</summary>

1. **Review current constructor**:
   - Location: `/var/www/html/web/modules/contrib/jsonrpc_mcp/src/Controller/McpToolsController.php`
   - Current parameters: `$toolDiscovery`, `$normalizer`
   - Uses PHP 8 constructor property promotion

2. **Add handler parameter to constructor**:
   - Add after existing parameters: `protected HandlerInterface $handler`
   - Maintains property promotion pattern
   - Order: discovery service, normalizer, handler (logical grouping)

3. **Add use statement**:
   - Add at top of file: `use Drupal\jsonrpc\HandlerInterface;`
   - Place alphabetically with other use statements

4. **Update create() method**:
   - Add third parameter to `new self()`: `$container->get('jsonrpc.handler')`
   - Service ID is `jsonrpc.handler` (provided by jsonrpc module)
   - Keep existing PHPStan ignore comment

5. **Verify service definition**:
   - Check if `jsonrpc_mcp.services.yml` exists
   - If it exists, update controller service definition to include handler argument
   - If it doesn't exist, controller uses default container autowiring (common for controllers)
   - Controllers typically don't need explicit service definitions if using `create()` method

6. **Service ID reference**:
   - The JSON-RPC handler service ID is `jsonrpc.handler`
   - This is defined in the jsonrpc module's services.yml
   - Interface: `Drupal\jsonrpc\HandlerInterface`
   - Primary method: `execute()` for running JSON-RPC methods

7. **Type safety**:
   - Constructor parameter type: `HandlerInterface`
   - Property type: `protected HandlerInterface $handler`
   - Ensures type safety for invoke endpoint implementation

8. **Testing dependency injection**:
   - After changes, run `vendor/bin/drush cache:rebuild`
   - Access any route using the controller (e.g., /mcp/tools/list)
   - If no errors, dependency injection is working correctly
   - Check for "Error: Class ... constructor" errors indicating DI issues

9. **Common pitfalls**:
   - Incorrect service ID: Must be `jsonrpc.handler` (not `jsonrpc.handler_interface`)
   - Missing use statement for `HandlerInterface`
   - Forgetting to update both constructor AND create() method
   - Wrong parameter order in create() (must match constructor order)

10. **Why this is a separate task**: - Handler needed for invoke endpoint (Task 5) - Allows Task 3 (describe) and Task 5 (invoke) to be developed in parallel - Cleaner separation of dependency injection changes from business logic
</details>
