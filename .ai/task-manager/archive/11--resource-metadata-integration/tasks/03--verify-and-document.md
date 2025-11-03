---
id: 3
group: "validation"
dependencies: [2]
status: "completed"
created: "2025-11-03"
skills:
  - testing
  - documentation
---
# Manual Verification and Documentation

## Objective

Perform manual verification of the OAuth 2.0 Protected Resource Metadata integration and update project documentation with implementation details.

## Skills Required

- **testing**: Manual API testing with curl/jq, endpoint validation, RFC compliance verification
- **documentation**: Technical writing for developer documentation

## Acceptance Criteria

- [ ] Metadata endpoint returns valid JSON with MCP tool scopes
- [ ] RFC 9728 required fields present (`resource`, `authorization_servers`)
- [ ] MCP-contributed fields present and correctly formatted
- [ ] Cache invalidation tested (metadata updates when modules enabled/disabled)
- [ ] PHPStan passes with zero errors on all code
- [ ] Drupal coding standards pass on all code
- [ ] AGENTS.md updated with OAuth2 metadata integration details

Use your internal Todo tool to track these and keep on track.

## Technical Requirements

**Manual Test Cases:**

1. **Basic Metadata Retrieval**
   ```bash
   curl https://drupal-site/.well-known/oauth-protected-resource | jq
   ```

2. **Scope Discovery**
   ```bash
   curl https://drupal-site/.well-known/oauth-protected-resource | jq '.scopes_supported'
   ```

3. **Bearer Methods**
   ```bash
   curl https://drupal-site/.well-known/oauth-protected-resource | jq '.bearer_methods_supported'
   # Expected: ["header"]
   ```

4. **Authorization Details Types**
   ```bash
   curl https://drupal-site/.well-known/oauth-protected-resource | jq '.authorization_details_types_supported'
   # Expected: array of tool names like ["cache.rebuild", "node.create", ...]
   ```

5. **Cache Invalidation**
   ```bash
   # Get initial metadata
   curl https://drupal-site/.well-known/oauth-protected-resource | jq '.scopes_supported' > before.json

   # Clear cache
   vendor/bin/drush cr

   # Get metadata again
   curl https://drupal-site/.well-known/oauth-protected-resource | jq '.scopes_supported' > after.json

   # Should be identical
   diff before.json after.json
   ```

## Input Dependencies

- Task 2: Completed ResourceMetadataSubscriber implementation
- Working Drupal 11 installation with simple_oauth_server_metadata enabled
- At least one MCP tool with auth annotations for testing

## Output Artifacts

- Manual test results documented
- Updated AGENTS.md with integration details
- PHPStan and phpcs clean status confirmed

## Implementation Notes

<details>
<summary>Detailed Implementation Guide</summary>

### Manual Testing Procedure

**Prerequisites:**
```bash
# Ensure simple_oauth_server_metadata is enabled
vendor/bin/drush pm:list | grep simple_oauth_server_metadata

# Clear caches before testing
vendor/bin/drush cr
```

**Test 1: Endpoint Accessibility**
```bash
# Should return 200 OK with JSON response
curl -I https://drupal-site/.well-known/oauth-protected-resource

# Expected headers:
# HTTP/1.1 200 OK
# Content-Type: application/json
# Access-Control-Allow-Origin: *
```

**Test 2: RFC 9728 Compliance**
```bash
curl https://drupal-site/.well-known/oauth-protected-resource | jq '
{
  has_resource: has("resource"),
  has_authorization_servers: has("authorization_servers"),
  has_bearer_methods: has("bearer_methods_supported"),
  has_scopes: has("scopes_supported"),
  has_auth_details: has("authorization_details_types_supported")
}
'

# Expected output:
# {
#   "has_resource": true,
#   "has_authorization_servers": true,
#   "has_bearer_methods": true,
#   "has_scopes": true,
#   "has_auth_details": true
# }
```

**Test 3: Field Value Validation**
```bash
# Validate bearer_methods_supported
curl https://drupal-site/.well-known/oauth-protected-resource | \
  jq '.bearer_methods_supported | contains(["header"])'
# Expected: true

# Validate scopes_supported is an array
curl https://drupal-site/.well-known/oauth-protected-resource | \
  jq '.scopes_supported | type'
# Expected: "array"

# Validate authorization_details_types_supported is an array
curl https://drupal-site/.well-known/oauth-protected-resource | \
  jq '.authorization_details_types_supported | type'
# Expected: "array"
```

**Test 4: Integration with Tool Discovery**

Create a test tool to verify integration:

```php
// In a test module
#[JsonRpcMethod(
  id: "test.example",
  usage: new TranslatableMarkup("Test method"),
  access: ["access content"]
)]
#[McpTool(
  title: "Test Example",
  annotations: [
    'auth' => [
      'level' => 'required',
      'scopes' => ['test_scope_1', 'test_scope_2']
    ]
  ]
)]
class TestExample extends JsonRpcMethodBase {
  public function execute(ParameterBag $params): array {
    return ['result' => 'test'];
  }
}
```

Enable the test module and verify scopes appear:
```bash
vendor/bin/drush pm:enable test_module
vendor/bin/drush cr

curl https://drupal-site/.well-known/oauth-protected-resource | \
  jq '.scopes_supported | contains(["test_scope_1", "test_scope_2"])'
# Expected: true
```

### Code Quality Validation

**PHPStan:**
```bash
vendor/bin/phpstan analyse src/EventSubscriber/
# Expected: 0 errors
```

**Coding Standards:**
```bash
vendor/bin/phpcs --standard=Drupal,DrupalPractice src/EventSubscriber/
# Expected: 0 errors, 0 warnings
```

**Auto-fix Minor Issues:**
```bash
vendor/bin/phpcbf --standard=Drupal,DrupalPractice src/EventSubscriber/
```

### Documentation Updates

**Update AGENTS.md** - Add new section after "Per-Tool Invocation Endpoints":

```markdown
### OAuth 2.0 Protected Resource Metadata Integration

The module integrates with simple_oauth_21's `simple_oauth_server_metadata` sub-module to contribute MCP tool metadata to the OAuth 2.0 Protected Resource Metadata endpoint (`/.well-known/oauth-protected-resource`).

**Architecture:**

- `ResourceMetadataSubscriber` listens to `ResourceMetadataEvents::BUILD`
- Discovers all MCP tools via `McpToolDiscoveryService`
- Extracts OAuth2 scopes from `#[McpTool(annotations: ['auth' => ['scopes' => [...]]])]`
- Contributes RFC 9728-compliant fields to metadata response

**Contributed Fields:**

- `scopes_supported`: Aggregated array of all unique scopes from MCP tools
- `bearer_methods_supported`: Always `["header"]` per MCP specification
- `authorization_details_types_supported`: Array of tool names for fine-grained authorization

**Example Response:**

\```json
{
  "resource": "https://drupal-site.example.com",
  "authorization_servers": ["https://drupal-site.example.com"],
  "bearer_methods_supported": ["header"],
  "scopes_supported": [
    "mcp_tools",
    "cache_management",
    "content_management"
  ],
  "authorization_details_types_supported": [
    "cache.rebuild",
    "node.create",
    "user.query"
  ]
}
\```

**Cache Behavior:**

- Metadata endpoint is cached by `ResourceMetadataService`
- Cache invalidates when:
  - Modules are installed/uninstalled (triggers `jsonrpc_mcp:discovery` tag invalidation)
  - Cache is manually cleared (`drush cr`)
  - Configuration changes (via `config:simple_oauth_server_metadata.settings` tag)

**Testing:**

\```bash
# View metadata
curl https://drupal-site/.well-known/oauth-protected-resource | jq

# Verify MCP tool scopes
curl https://drupal-site/.well-known/oauth-protected-resource | jq '.scopes_supported'

# Test cache invalidation
vendor/bin/drush cr
curl https://drupal-site/.well-known/oauth-protected-resource | jq
\```

**Optional Dependency:**

The `simple_oauth_server_metadata` sub-module is an optional dependency. If not installed, the event subscriber will not be called and no errors will occur.
```

### Success Validation Checklist

- [ ] Endpoint returns valid JSON
- [ ] All RFC 9728 required fields present
- [ ] MCP-contributed fields have correct data types
- [ ] Scopes from test tool appear in metadata
- [ ] Cache invalidation works correctly
- [ ] PHPStan reports zero errors
- [ ] phpcs reports zero errors and warnings
- [ ] AGENTS.md updated with integration details
- [ ] Manual test results documented (can be in this task file)

### Troubleshooting

**Problem:** Metadata endpoint returns 500 error
- Check Drupal logs: `vendor/bin/drush watchdog:show`
- Verify service registration: `vendor/bin/drush ev "\Drupal::service('jsonrpc_mcp.resource_metadata_subscriber');"`
- Check simple_oauth_server_metadata is enabled

**Problem:** MCP fields not appearing in metadata
- Clear cache: `vendor/bin/drush cr`
- Verify event subscriber is registered: Check `jsonrpc_mcp.services.yml`
- Verify tools are discovered: `vendor/bin/drush ev "print_r(\Drupal::service('jsonrpc_mcp.tool_discovery')->discoverTools());"`

**Problem:** Scopes empty even though tools have auth annotations
- Check #[McpTool] annotation syntax in tool classes
- Verify annotations array structure: `'auth' => ['scopes' => [...]]`
- Add debug logging to `onBuildResourceMetadata()` to inspect extracted data

</details>
