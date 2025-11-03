---
id: 4
group: 'authentication'
dependencies: [3]
status: 'completed'
created: 2025-11-03
skills:
  - drupal-backend
  - oauth2
---

# Add Anonymous User Authentication Checking

## Objective

Add authentication requirement checking to `McpToolInvokeController` that returns RFC 6750 compliant 401 responses with WWW-Authenticate headers when anonymous users access tools requiring authentication.

## Skills Required

- **drupal-backend**: Drupal authentication system, user accounts
- **oauth2**: RFC 6750 bearer token challenges, WWW-Authenticate headers

## Acceptance Criteria

- [ ] Controller checks `annotations.auth.level` from tool metadata
- [ ] Returns 401 with WWW-Authenticate header for anonymous users accessing tools with `auth.level === 'required'`
- [ ] Header format: `Bearer realm="MCP Tools"`
- [ ] Includes proper cache headers: `Cache-Control: no-store`, `Pragma: no-cache`
- [ ] Authenticated users can proceed (OAuth2 validation happens in next task)

## Technical Requirements

**Modify** `src/Controller/McpToolInvokeController.php`:
- Add authentication check before delegation
- Load tool metadata from `$this->toolDiscovery->discoverTools()`
- Check if `$tool_data['annotations']['auth']['level'] === 'required'`
- If required and `$this->currentUser->isAnonymous()`, return 401

**Response format** for anonymous users:
```php
return new Response('', 401, [
  'WWW-Authenticate' => 'Bearer realm="MCP Tools"',
  'Cache-Control' => 'no-store',
  'Pragma' => 'no-cache',
]);
```

## Input Dependencies

- Task 3 complete (basic controller exists)
- Existing `#[McpTool(annotations: ['auth' => ['level' => 'required']])]` pattern in codebase

## Output Artifacts

- Modified `McpToolInvokeController.php` with authentication checking
- 401 responses for anonymous users accessing auth-required tools
- WWW-Authenticate headers that enable MCP client auth flows

<details>
<summary>Implementation Notes</summary>

**Add to `invoke()` method** (before existing payload parsing):

```php
public function invoke(Request $request, string $tool_name): Response {
  // Load tool metadata to check authentication requirements
  $tools = $this->toolDiscovery->discoverTools();

  if (!isset($tools[$tool_name])) {
    return new JsonResponse([
      'jsonrpc' => '2.0',
      'error' => [
        'code' => -32601,
        'message' => 'Method not found',
      ],
      'id' => NULL,
    ], 404);
  }

  $tool_data = $tools[$tool_name];
  $auth_level = $tool_data['annotations']['auth']['level'] ?? NULL;

  // Check if authentication is required
  if ($auth_level === 'required' && $this->currentUser->isAnonymous()) {
    return new Response('', 401, [
      'WWW-Authenticate' => 'Bearer realm="MCP Tools"',
      'Cache-Control' => 'no-store',
      'Pragma' => 'no-cache',
    ]);
  }

  // Continue with existing payload parsing and delegation...
```

**RFC 6750 Compliance**:
- **401 Unauthorized**: Indicates authentication is required
- **WWW-Authenticate header**: Challenge format telling client to use Bearer tokens
- **realm parameter**: Identifies the protection space ("MCP Tools")
- **Cache-Control**: Prevents caching of error responses

**Testing Authentication Flow**:
```bash
# Test with anonymous user (should return 401)
curl -i -X POST http://localhost/mcp/tools/cache.rebuild \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","method":"test","params":{},"id":"1"}'

# Look for:
# HTTP/1.1 401 Unauthorized
# WWW-Authenticate: Bearer realm="MCP Tools"
# Cache-Control: no-store
```

**Test Tool Setup** (for testing auth requirements):
Reference `tests/modules/jsonrpc_mcp_auth_test/src/Plugin/jsonrpc/Method/MethodWithAuth.php` for example tool with `auth.level === 'required'`.

**Key Points**:
1. This task only handles **anonymous user detection**
2. OAuth2 token validation comes in task 5
3. OAuth2 scope validation comes in task 6
4. For now, any authenticated user (cookie, basic auth, OAuth2) can proceed past this check
</details>
