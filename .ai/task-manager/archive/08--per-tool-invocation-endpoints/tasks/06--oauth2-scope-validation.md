---
id: 6
group: 'authentication'
dependencies: [5]
status: 'completed'
created: 2025-11-03
skills:
  - drupal-backend
  - oauth2
---

# Add OAuth2 Scope Validation

## Objective

Implement OAuth2 scope checking that validates tokens contain required scopes from `annotations.auth.scopes`, returning RFC 6750 compliant 403 responses with missing scope information.

## Skills Required

- **drupal-backend**: simple_oauth scope entities, scope provider service
- **oauth2**: Scope validation, `insufficient_scope` error format

## Acceptance Criteria

- [ ] Checks if tool defines required scopes in `annotations.auth.scopes`
- [ ] Extracts scopes from valid OAuth2 token
- [ ] Compares required scopes against token scopes
- [ ] Returns 403 with `insufficient_scope` error if scopes missing
- [ ] Header includes only missing scopes: `Bearer realm="MCP Tools", error="insufficient_scope", scope="missing_scope1 missing_scope2"`
- [ ] Tools without scope requirements proceed normally

## Technical Requirements

**Modify** `src/Controller/McpToolInvokeController.php`:
- After token validation, check `$tool_data['annotations']['auth']['scopes']`
- Get token scopes via `$token->get('scopes')->getScopes()`
- Compare required scopes vs token scopes (array_diff)
- Return 403 if scopes insufficient

**Response format for insufficient scopes**:
```php
$scope_string = implode(' ', $missing_scopes);
return new Response('', 403, [
  'WWW-Authenticate' => sprintf(
    'Bearer realm="MCP Tools", error="insufficient_scope", scope="%s"',
    $scope_string
  ),
  'Cache-Control' => 'no-store',
]);
```

## Input Dependencies

- Task 5 complete (OAuth2 token validation works)
- simple_oauth module provides scope functionality
- Test tools with `annotations.auth.scopes` defined

## Output Artifacts

- Modified `McpToolInvokeController.php` with scope validation
- 403 responses for tokens lacking required scopes
- Only missing scopes returned in error (not all required scopes)

<details>
<summary>Implementation Notes</summary>

**Add to `invoke()` method** (after token validation, still in `if ($is_bearer)` block):

```php
// After token validation (inside if ($is_bearer) block)...

// Check required scopes if defined
$required_scopes = $tool_data['annotations']['auth']['scopes'] ?? [];

if (!empty($required_scopes)) {
  // Get token scopes
  $token_scopes = $token->get('scopes')->getScopes();
  $token_scope_ids = array_map(fn($scope) => $scope->id(), $token_scopes);

  // Find missing scopes
  $missing_scopes = array_diff($required_scopes, $token_scope_ids);

  if (!empty($missing_scopes)) {
    $scope_string = implode(' ', $missing_scopes);
    return new Response('', 403, [
      'WWW-Authenticate' => sprintf(
        'Bearer realm="MCP Tools", error="insufficient_scope", scope="%s"',
        $scope_string
      ),
      'Cache-Control' => 'no-store',
    ]);
  }
}

// Continue with payload parsing and delegation...
```

**RFC 6750 Scope Validation**:
- **403 Forbidden**: Used when authentication succeeds but authorization fails
- **insufficient_scope error**: Indicates token lacks required permissions
- **scope parameter**: Space-delimited list of missing scopes (not all required scopes)

**Scope Format**:
The `annotations.auth.scopes` array contains scope identifiers (machine names or IDs). Example:
```php
#[McpTool(
  annotations: [
    'auth' => [
      'level' => 'required',
      'scopes' => ['content:read', 'content:write'],
    ],
  ]
)]
```

**Testing Scope Validation**:
```bash
# Create test token with limited scopes
# Then test invocation:
curl -i -X POST http://localhost/mcp/tools/cache.rebuild \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer token_with_limited_scopes" \
  -d '{"jsonrpc":"2.0","method":"test","params":{},"id":"1"}'

# Expected response if scopes insufficient:
# HTTP/1.1 403 Forbidden
# WWW-Authenticate: Bearer realm="MCP Tools", error="insufficient_scope", scope="missing_scope"
```

**Security Considerations**:
1. **Only return missing scopes** (not all required scopes) per plan requirements
2. **If scopes are defined, require OAuth2**: This prevents bypassing scope requirements via cookie auth
3. Consider adding check: if `required_scopes` is defined but user not authenticated via OAuth2, return 401

**Scope Bypass Prevention**:
Add this check before scope validation:
```php
if (!empty($required_scopes) && !$is_bearer) {
  // Scopes required but user not using OAuth2
  return new Response('', 401, [
    'WWW-Authenticate' => 'Bearer realm="MCP Tools"',
    'Cache-Control' => 'no-store',
  ]);
}
```

**Reference**: See `tests/modules/jsonrpc_mcp_auth_test/src/Plugin/jsonrpc/Method/MethodWithAuth.php` for example tool with scope requirements.
</details>
