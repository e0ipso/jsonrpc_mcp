---
id: 3
group: 'invocation'
dependencies: [2]
status: 'completed'
created: 2025-11-03
skills:
  - drupal-backend
  - php
---

# Implement Basic Invocation Controller with JSON-RPC Delegation

## Objective

Create `McpToolInvokeController` that accepts JSON-RPC payloads via POST requests and delegates execution to the JSON-RPC handler without transformation. No authentication logic yet - just basic delegation.

## Skills Required

- **drupal-backend**: Drupal controllers, JSON-RPC integration, service injection
- **php**: Exception handling, JSON processing

## Acceptance Criteria

- [ ] Class `src/Controller/McpToolInvokeController.php` created
- [ ] Service `jsonrpc_mcp.controller.invoke` defined with dependencies
- [ ] Controller accepts POST requests with JSON-RPC payload
- [ ] Method name in payload is overridden with route's `tool_name`
- [ ] Delegates to `jsonrpc.handler` service via `batch()` method
- [ ] Returns JSON-RPC response format (both success and error cases)
- [ ] Handles JSON-RPC notifications (requests with `id: null`)

## Technical Requirements

**Create** `src/Controller/McpToolInvokeController.php`:
- Method signature: `public function invoke(Request $request, string $tool_name): Response`
- Parse JSON from request body
- Validate JSON-RPC structure (jsonrpc: "2.0", method, params, id)
- Override method with `$tool_name` from route
- Create `RpcRequest` object and call `$this->handler->batch([$rpc_request])`
- Return `JsonResponse` with RPC response or error

**Update** `jsonrpc_mcp.services.yml`:
```yaml
jsonrpc_mcp.controller.invoke:
  class: Drupal\jsonrpc_mcp\Controller\McpToolInvokeController
  arguments:
    - '@jsonrpc_mcp.tool_discovery'
    - '@jsonrpc.handler'
    - '@entity_type.manager'
    - '@current_user'
```

**Note**: OAuth2 scope provider will be added in later task.

## Input Dependencies

- Task 2 complete (routes exist pointing to this controller)
- Existing `jsonrpc.handler` service
- Existing `jsonrpc_mcp.tool_discovery` service

## Output Artifacts

- `src/Controller/McpToolInvokeController.php` - Basic invocation controller
- Updated `jsonrpc_mcp.services.yml` with controller service
- Working POST invocation for tools (without OAuth2 validation)

<details>
<summary>Implementation Notes</summary>

**File: `src/Controller/McpToolInvokeController.php`**

```php
<?php

declare(strict_types=1);

namespace Drupal\jsonrpc_mcp\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jsonrpc\Exception\JsonRpcException;
use Drupal\jsonrpc\HandlerInterface;
use Drupal\jsonrpc\JsonRpcObject\ParameterBag;
use Drupal\jsonrpc\JsonRpcObject\Request as RpcRequest;
use Drupal\jsonrpc_mcp\Service\McpToolDiscoveryService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for per-tool MCP invocation endpoints.
 */
class McpToolInvokeController extends ControllerBase {

  /**
   * Constructs a new McpToolInvokeController.
   */
  public function __construct(
    protected McpToolDiscoveryService $toolDiscovery,
    protected HandlerInterface $handler,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('jsonrpc_mcp.tool_discovery'),
      $container->get('jsonrpc.handler'),
      $container->get('entity_type.manager'),
      $container->get('current_user'),
    );
  }

  /**
   * Invokes a specific MCP tool with JSON-RPC payload.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request.
   * @param string $tool_name
   *   The tool name from the route.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
  public function invoke(Request $request, string $tool_name): Response {
    // TODO: Authentication and OAuth2 scope validation will be added in later tasks

    // Parse JSON-RPC payload from POST body
    $content = $request->getContent();

    try {
      $rpc_payload = Json::decode($content);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'jsonrpc' => '2.0',
        'error' => [
          'code' => -32700,
          'message' => 'Parse error',
        ],
        'id' => NULL,
      ], 400);
    }

    // Validate JSON decode success
    if ($rpc_payload === NULL || !is_array($rpc_payload)) {
      return new JsonResponse([
        'jsonrpc' => '2.0',
        'error' => [
          'code' => -32700,
          'message' => 'Parse error',
        ],
        'id' => NULL,
      ], 400);
    }

    // Validate JSON-RPC structure
    if (!isset($rpc_payload['jsonrpc']) || $rpc_payload['jsonrpc'] !== '2.0') {
      return new JsonResponse([
        'jsonrpc' => '2.0',
        'error' => [
          'code' => -32600,
          'message' => 'Invalid Request',
        ],
        'id' => $rpc_payload['id'] ?? NULL,
      ], 400);
    }

    // Override method with route tool name (ignore any method in payload)
    $rpc_payload['method'] = $tool_name;

    return $this->delegateToJsonRpc($rpc_payload);
  }

  /**
   * Delegates execution to JSON-RPC handler.
   *
   * @param array $rpc_payload
   *   The JSON-RPC payload.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
  protected function delegateToJsonRpc(array $rpc_payload): Response {
    try {
      $version = $this->handler::supportedVersion();
      $params = new ParameterBag($rpc_payload['params'] ?? []);
      $id = $rpc_payload['id'] ?? NULL;
      $is_notification = $id === NULL;

      $rpc_request = new RpcRequest(
        $version,
        $rpc_payload['method'],
        $is_notification,
        $id,
        $params
      );

      $rpc_responses = $this->handler->batch([$rpc_request]);

      if (empty($rpc_responses)) {
        // This is a notification (no response expected)
        return new Response('', 204);
      }

      $rpc_response = reset($rpc_responses);

      // Return JSON-RPC response as-is (includes error or result)
      return new JsonResponse($rpc_response->getSerializedObject());

    }
    catch (JsonRpcException $e) {
      return new JsonResponse([
        'jsonrpc' => '2.0',
        'error' => [
          'code' => $e->getCode(),
          'message' => $e->getMessage(),
        ],
        'id' => $rpc_payload['id'] ?? NULL,
      ], 500);
    }
  }

}
```

**Key Implementation Points**:

1. **No transformation**: Payload structure matches JSON-RPC exactly
2. **Method override**: Always use route-derived `$tool_name`, ignore payload's method field
3. **Error format**: JSON-RPC error format for RPC errors, proper HTTP status codes
4. **Notifications**: Return 204 No Content for notifications (id: null)
5. **Pass-through**: JSON-RPC Handler::batch() handles permission checking automatically

**Testing Basic Invocation**:
```bash
# Test POST request
curl -X POST http://localhost/mcp/tools/cache.rebuild \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0",
    "method": "ignored",
    "params": {},
    "id": "test-1"
  }'
```

**Dependencies needed**: Make sure these use statements are included:
- `use Drupal\jsonrpc\JsonRpcObject\Request as RpcRequest;` (alias to avoid conflict)
- All other imports as shown above

**Note**: Authentication/authorization logic will be added in tasks 4-6. This task focuses only on basic delegation.
</details>
