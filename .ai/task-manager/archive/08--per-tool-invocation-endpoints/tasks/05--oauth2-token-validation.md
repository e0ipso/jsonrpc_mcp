---
id: 5
group: 'authentication'
dependencies: [4]
status: 'completed'
created: 2025-11-03
skills:
  - drupal-backend
  - oauth2
---

# Add OAuth2 Token Validation

## Objective

Implement OAuth2 bearer token detection, loading, and validation in the invocation controller, returning RFC 6750 compliant 401 responses for invalid/expired tokens.

## Skills Required

- **drupal-backend**: Drupal entity API, simple_oauth module integration
- **oauth2**: Bearer token extraction, token validation, RFC 6750 error codes

## Acceptance Criteria

- [ ] Detects OAuth2 authentication via `Authorization: Bearer` header
- [ ] Loads token entity from `oauth2_token` storage
- [ ] Validates token exists, not revoked, and not expired
- [ ] Returns 401 with `invalid_token` error for invalid/expired tokens
- [ ] Header format: `Bearer realm="MCP Tools", error="invalid_token", error_description="..."`
- [ ] Non-OAuth2 authenticated users can still proceed (cookie/basic auth)

## Technical Requirements

**Modify** `src/Controller/McpToolInvokeController.php`:
- After anonymous user check, add OAuth2 token validation
- Extract bearer token from `Authorization` header
- Load token from `oauth2_token` entity storage
- Check `$token->isRevoked()` and `$token->get('expire')->value < time()`
- Return 401 if validation fails

**Error response format**:
```php
return new Response('', 401, [
  'WWW-Authenticate' => 'Bearer realm="MCP Tools", error="invalid_token", error_description="The access token is invalid or expired"',
  'Cache-Control' => 'no-store',
]);
```

**Note**: Only validate if Bearer token is present. Cookie/basic auth users skip this validation.

## Input Dependencies

- Task 4 complete (anonymous user checking works)
- simple_oauth module installed (provides `oauth2_token` entity type)
- `EntityTypeManagerInterface` already injected in controller

## Output Artifacts

- Modified `McpToolInvokeController.php` with OAuth2 token validation
- 401 responses with `invalid_token` error for bad tokens
- Valid OAuth2 tokens can proceed to scope validation (next task)

<details>
<summary>Implementation Notes</summary>

**Add to `invoke()` method** (after anonymous user check, before delegation):

```php
// After anonymous user check...

// Detect OAuth2 authentication
$authorization = $request->headers->get('Authorization');
$is_bearer = $authorization && str_starts_with($authorization, 'Bearer ');

if ($is_bearer) {
  // Extract token value
  $token_value = substr($authorization, 7);

  // Load token entity
  $token_storage = $this->entityTypeManager->getStorage('oauth2_token');
  $tokens = $token_storage->loadByProperties(['value' => $token_value]);
  $token = $tokens ? reset($tokens) : NULL;

  // Validate token
  if (!$token || $token->isRevoked() || $token->get('expire')->value < time()) {
    return new Response('', 401, [
      'WWW-Authenticate' => 'Bearer realm="MCP Tools", error="invalid_token", error_description="The access token is invalid or expired"',
      'Cache-Control' => 'no-store',
    ]);
  }

  // Store token for scope validation in next task
  // For now, just continue to delegation
}

// Continue with payload parsing and delegation...
```

**RFC 6750 Error Codes**:
- `invalid_token`: Token is malformed, expired, revoked, or does not exist
- This is the correct error code for all token validation failures
- Do NOT use `invalid_request` (that's for malformed requests, not bad tokens)

**Testing OAuth2 Token Validation**:
```bash
# Test with invalid token
curl -i -X POST http://localhost/mcp/tools/cache.rebuild \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer invalid_token_here" \
  -d '{"jsonrpc":"2.0","method":"test","params":{},"id":"1"}'

# Expected response:
# HTTP/1.1 401 Unauthorized
# WWW-Authenticate: Bearer realm="MCP Tools", error="invalid_token", error_description="The access token is invalid or expired"
```

**Entity API Usage**:
```php
// oauth2_token entity provides these methods:
$token->isRevoked()  // Returns bool
$token->get('expire')->value  // Returns Unix timestamp
```

**Security Note**: Use the same error message for all token validation failures (not found, expired, revoked) to prevent token enumeration attacks.

**Scope Validation**: Task 6 will add scope checking. For now, valid tokens with any scopes can proceed.
</details>
