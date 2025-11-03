---
id: 8
group: 'routing'
dependencies: [7]
status: 'completed'
created: 2025-11-03
skills:
  - drupal-backend
---

# Implement Access Check Service

## Objective

Create `McpToolAccessCheck` service that provides lightweight route-level access checking for tools requiring authentication, preventing anonymous access at the routing layer.

## Skills Required

- **drupal-backend**: Drupal access control system, access check services, route requirements

## Acceptance Criteria

- [ ] Class `src/Access/McpToolAccessCheck.php` created implementing `AccessInterface`
- [ ] Service `jsonrpc_mcp.access_check.mcp_tool` defined with `access_check` tag
- [ ] Checks if tool requires authentication (`auth.level === 'required'`)
- [ ] Returns forbidden access for anonymous users on auth-required tools
- [ ] Returns allowed access for authenticated users or public tools
- [ ] Proper cache contexts added (`user`, `user.roles:anonymous`)

## Technical Requirements

**Create** `src/Access/McpToolAccessCheck.php`:
- Implement `AccessInterface`
- Method: `access(Route $route, AccountInterface $account): AccessResult`
- Load tool metadata using tool_name from route
- Check `auth.level === 'required'` and `$account->isAnonymous()`
- Return `AccessResult::forbidden()` or `AccessResult::allowed()` with cache metadata

**Update** `jsonrpc_mcp.services.yml`:
```yaml
jsonrpc_mcp.access_check.mcp_tool:
  class: Drupal\jsonrpc_mcp\Access\McpToolAccessCheck
  arguments:
    - '@jsonrpc_mcp.tool_discovery'
  tags:
    - { name: access_check, applies_to: _custom_access }
```

**Note**: Routes with `auth.level === 'required'` were configured to use `_custom_access` in task 2.

## Input Dependencies

- Task 7 complete (full invocation logic works)
- Task 2 routes configured with conditional `_custom_access` requirement
- `McpToolDiscoveryService` available for injecting

## Output Artifacts

- `src/Access/McpToolAccessCheck.php` - Access check service
- Updated `jsonrpc_mcp.services.yml` with access check service
- Route-level access control for auth-required tools

<details>
<summary>Implementation Notes</summary>

**File: `src/Access/McpToolAccessCheck.php`**

```php
<?php

declare(strict_types=1);

namespace Drupal\jsonrpc_mcp\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\jsonrpc_mcp\Service\McpToolDiscoveryService;
use Symfony\Component\Routing\Route;

/**
 * Access check for MCP tool routes.
 */
class McpToolAccessCheck implements AccessInterface {

  /**
   * Constructs a new McpToolAccessCheck.
   */
  public function __construct(
    protected McpToolDiscoveryService $toolDiscovery,
  ) {}

  /**
   * Checks access to MCP tool routes.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check access for.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to check access for.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, AccountInterface $account): AccessResultInterface {
    $tool_name = $route->getDefault('tool_name');

    // Load tool metadata
    $tools = $this->toolDiscovery->discoverTools();
    if (!isset($tools[$tool_name])) {
      return AccessResult::forbidden('Tool not found')
        ->addCacheTags(['jsonrpc_mcp:discovery']);
    }

    $tool = $tools[$tool_name];
    $auth_level = $tool['annotations']['auth']['level'] ?? NULL;

    // If authentication required, deny anonymous access
    // (Controller will return proper 401 with WWW-Authenticate)
    if ($auth_level === 'required' && $account->isAnonymous()) {
      return AccessResult::forbidden('Authentication required')
        ->addCacheContexts(['user.roles:anonymous'])
        ->addCacheTags(['jsonrpc_mcp:discovery']);
    }

    // Allow access - detailed checks happen in controller
    return AccessResult::allowed()
      ->addCacheContexts(['user'])
      ->addCacheTags(['jsonrpc_mcp:discovery']);
  }

}
```

**Key Implementation Details**:

1. **This is NOT authorization** - it's a lightweight gate for authentication
2. **Controller still performs full validation** - OAuth2 token checking, scope validation, etc.
3. **Cache contexts**:
   - `user`: Access varies by user
   - `user.roles:anonymous`: Specifically for anonymous role check
4. **Cache tags**:
   - `jsonrpc_mcp:discovery`: Invalidate when tools change

**Purpose of This Access Check**:
- Prevents Drupal from even routing the request if user is anonymous and auth is required
- Provides early rejection before controller logic
- BUT controller must still return proper RFC 6750 responses (the controller does this in task 4)

**Testing Access Check**:
```bash
# Anonymous user accessing auth-required tool
# Should get 403 Forbidden from Drupal routing (not reach controller)
curl -i http://localhost/mcp/tools/auth-required-tool

# Authenticated user accessing same tool
# Should reach controller (which then checks OAuth2 token/scopes)
curl -i http://localhost/mcp/tools/auth-required-tool \
  --cookie "SESS..." # or with Authorization header
```

**Relationship to Controller Auth**:
- **Route access check**: Prevents anonymous access (returns 403 from Drupal routing)
- **Controller auth check**: Returns 401 with WWW-Authenticate for MCP clients

Why both? The route check is standard Drupal access control. The controller check provides RFC 6750 compliant responses for MCP clients.

**Service Tag**: The `access_check` tag with `applies_to: _custom_access` tells Drupal to use this service for routes with `_custom_access` requirement.
</details>
