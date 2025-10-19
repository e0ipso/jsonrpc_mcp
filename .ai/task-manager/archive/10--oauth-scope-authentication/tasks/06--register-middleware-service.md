---
id: 6
group: "service-configuration"
dependencies: [5]
status: "completed"
created: "2025-10-19"
skills:
  - drupal-backend
---
# Register OAuth Scope Validator Middleware in Services

## Objective
Register the OAuthScopeValidator middleware in Drupal's service container with proper dependency injection and middleware priority.

## Skills Required
- **drupal-backend**: Understanding of Drupal service configuration, dependency injection, and HTTP middleware stack

## Acceptance Criteria
- [ ] File `jsonrpc_mcp.services.yml` is updated
- [ ] Service `jsonrpc_mcp.middleware.oauth_scope_validator` is registered
- [ ] Service is tagged with `http_middleware` tag
- [ ] Middleware priority is set appropriately (after authentication, before routing)
- [ ] Dependencies are correctly configured (http_kernel, tool_discovery service)
- [ ] Service configuration follows Drupal standards

Use your internal Todo tool to track these and keep on track.

## Technical Requirements
- **File**: `jsonrpc_mcp.services.yml`
- **Service ID**: `jsonrpc_mcp.middleware.oauth_scope_validator`
- **Class**: `Drupal\jsonrpc_mcp\Middleware\OAuthScopeValidator`
- **Tag**: `http_middleware` with priority ~200 (after auth, before route matching)
- **Arguments**: `@http_kernel`, `@jsonrpc_mcp.tool_discovery`

## Input Dependencies
- Task 5: Requires OAuthScopeValidator class to exist

## Output Artifacts
- Updated `jsonrpc_mcp.services.yml` - Enables middleware in HTTP stack

<details>
<summary>Implementation Notes</summary>

### File Location
Update existing file: `jsonrpc_mcp.services.yml`

### Service Configuration
Add the following service definition to the services file:

```yaml
services:
  jsonrpc_mcp.middleware.oauth_scope_validator:
    class: Drupal\jsonrpc_mcp\Middleware\OAuthScopeValidator
    arguments:
      - '@http_kernel'
      - '@jsonrpc_mcp.tool_discovery'
    tags:
      - { name: http_middleware, priority: 200, responder: true }
```

### Configuration Details

**Arguments**:
- `@http_kernel`: The wrapped HTTP kernel (standard middleware pattern)
- `@jsonrpc_mcp.tool_discovery`: Tool discovery service for loading tool definitions

**Tag Parameters**:
- `name: http_middleware`: Registers as HTTP middleware
- `priority: 200`: Executes after authentication (300) but before routing (100)
- `responder: true`: Middleware can generate responses (403 errors)

### Middleware Priority Context
Drupal HTTP middleware priorities:
- 300: Page cache
- 250: Authentication
- **200**: OAuth scope validation (our middleware)
- 100: Routing
- 50: Session

Our middleware needs to:
1. Run AFTER authentication to access authenticated user token
2. Run BEFORE routing to intercept invoke endpoint early
3. Priority 200 achieves this positioning

### Existing Services Context
The file should already contain:
- `jsonrpc_mcp.tool_discovery`: Service we depend on
- `jsonrpc_mcp.tool_normalizer`: Related service

Ensure our service is added in a logical location near related services.

### Verification
After updating the file:
1. Clear Drupal cache: `vendor/bin/drush cache:rebuild`
2. Verify service registration: `vendor/bin/drush debug:container jsonrpc_mcp.middleware.oauth_scope_validator`
3. Check middleware stack: Look for our middleware in HTTP kernel
4. Test that middleware intercepts `/mcp/tools/invoke` requests
5. Verify other endpoints are not affected
</details>
