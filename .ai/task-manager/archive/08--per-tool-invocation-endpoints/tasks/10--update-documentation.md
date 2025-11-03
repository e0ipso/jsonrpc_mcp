---
id: 10
group: 'documentation'
dependencies: [9]
status: 'completed'
created: 2025-11-03
skills:
  - technical-writing
---

# Update Module Documentation

## Objective

Update README.md and AGENTS.md with brief descriptions of the new per-tool URL invocation pattern, OAuth2 authentication flow support, and RFC 6750 compliance.

## Skills Required

- **technical-writing**: Clear technical documentation, code examples, standards references

## Acceptance Criteria

- [ ] README.md updated with Per-Tool Invocation section
- [ ] README.md updated with Authentication Flow Support section
- [ ] README.md updated with Supported Standards section
- [ ] AGENTS.md Architecture section updated for dynamic routes
- [ ] AGENTS.md updated with Authentication Requirements section
- [ ] AGENTS.md Testing section updated with OAuth2 examples
- [ ] All sections kept brief (2-3 paragraphs maximum)
- [ ] Practical curl examples included

## Technical Requirements

**README.md additions**:

1. **Per-Tool Invocation section**:
   - URL pattern: `/mcp/tools/{tool_name}`
   - GET and POST support
   - Direct JSON-RPC payload acceptance
   - Example curl command

2. **Authentication Flow Support section**:
   - RFC 6750 OAuth2 bearer token authentication
   - Automatic authentication flow triggering (401/403)
   - OAuth2 scope validation
   - WWW-Authenticate header examples

3. **Supported Standards section**:
   - MCP Specification (2025-06-18)
   - RFC 6750: OAuth 2.0 Bearer Token Usage
   - JSON-RPC 2.0 Specification

**AGENTS.md additions**:

1. **Architecture section updates**:
   - Dynamic route generation via route callback
   - OAuth2 authentication flow implementation
   - Per-tool URL pattern

2. **Authentication Requirements section** (new):
   - `annotations.auth.level` usage
   - `annotations.auth.scopes` for OAuth2 scopes
   - Authentication flow: anonymous → 401, invalid token → 401, insufficient scopes → 403

3. **Testing MCP Endpoints section updates**:
   - Per-tool URL examples
   - OAuth2 token generation for testing
   - Bearer token examples

## Input Dependencies

- Task 9 complete (implementation fully tested)
- Plan document sections for reference (see plan's "Documentation Updates" section)

## Output Artifacts

- Updated README.md with new features documented
- Updated AGENTS.md with implementation details
- Clear examples for developers using the module

<details>
<summary>Implementation Notes</summary>

**Keep documentation BRIEF** - 2-3 paragraphs per section maximum. Focus on practical usage.

**Example content for README.md**:

```markdown
## Per-Tool Invocation

Each MCP tool is accessible at its own URL following the pattern `/mcp/tools/{tool_name}`, where `{tool_name}` is the JSON-RPC method ID (e.g., `cache.rebuild`). Tools accept standard JSON-RPC 2.0 payloads via both POST (JSON body) and GET (URL-encoded query parameter) requests, eliminating payload transformation overhead.

Example invocation:
\`\`\`bash
# POST request with JSON-RPC payload
curl -X POST https://example.com/mcp/tools/cache.rebuild \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -d '{
    "jsonrpc": "2.0",
    "method": "cache.rebuild",
    "params": {},
    "id": "1"
  }'

# GET request with query parameter (URL-encoded)
curl "https://example.com/mcp/tools/cache.rebuild?query=%7B%22jsonrpc%22%3A%222.0%22%2C%22method%22%3A%22cache.rebuild%22%2C%22params%22%3A%7B%7D%2C%22id%22%3A%221%22%7D" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
\`\`\`

## Authentication Flow Support

The module implements RFC 6750 compliant OAuth2 bearer token authentication with automatic authentication flow triggering. When tools require authentication (`annotations.auth.level === 'required'` in the `#[McpTool]` attribute), the module returns proper 401 Unauthorized responses with `WWW-Authenticate` headers, enabling MCP clients like Claude Code to automatically initiate authentication flows.

OAuth2 scope validation ensures tokens contain required scopes specified in `annotations.auth.scopes`. Insufficient scopes result in 403 Forbidden responses with missing scope information. This enables fine-grained access control beyond Drupal's permission system.

Authentication flow responses:
- **Anonymous user**: `401 Unauthorized` with `WWW-Authenticate: Bearer realm="MCP Tools"`
- **Invalid/expired token**: `401 Unauthorized` with `error="invalid_token"`
- **Insufficient scopes**: `403 Forbidden` with `error="insufficient_scope", scope="missing_scopes"`

## Supported Standards

- **MCP Specification (2025-06-18)**: Tool discovery and invocation protocol
- **RFC 6750**: OAuth 2.0 Bearer Token Usage - Authentication challenges and error responses
- **JSON-RPC 2.0**: Request/response message format
```

**Example content for AGENTS.md** (add to Architecture section):

```markdown
### Per-Tool Invocation Architecture

The module dynamically generates one route per discovered MCP tool using Drupal's route callback system. The `McpToolRoutes` service scans all tools via `McpToolDiscoveryService` and creates routes at `/mcp/tools/{tool_name}`. Routes are regenerated on cache clears and module installations.

The `McpToolInvokeController` implements OAuth2 authentication flow with RFC 6750 compliant error responses:
1. Checks `annotations.auth.level` for authentication requirements
2. Validates OAuth2 bearer tokens if present (checks expiration, revocation)
3. Validates token scopes against `annotations.auth.scopes` requirements
4. Delegates to JSON-RPC handler for execution

This architecture enables automatic authentication flow triggering in MCP clients while maintaining full compatibility with Drupal's authentication and JSON-RPC's permission systems.
```

**Content Guidelines**:
- Focus on WHAT and HOW, not WHY (assume developers understand OAuth2)
- Include working code examples
- Reference RFC 6750 for authentication flow details
- Document the distinction between 401 (authentication) and 403 (authorization)
- Keep it actionable - developers should be able to use the feature after reading

**Do NOT**:
- Add verbose explanations of OAuth2 concepts
- Document internal implementation details
- Include lengthy tutorials
- Add diagrams or complex visualizations

**Testing Documentation**:
Read the updated files to ensure they're clear, concise, and include working examples.
</details>
