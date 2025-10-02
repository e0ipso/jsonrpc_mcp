---
id: 1
group: 'api-documentation'
dependencies: []
status: 'completed'
created: '2025-10-02'
skills:
  - technical-writing
  - api-documentation
---

# Create API Reference Section for Discovery Endpoints

## Objective

Create a comprehensive "API Reference" section in README.md that documents all three MCP discovery endpoints (`/mcp/tools/list`, `/mcp/tools/describe`, `/mcp/tools/invoke`) with complete request/response schemas, parameters, status codes, and practical examples.

## Skills Required

- **technical-writing**: Professional API documentation writing with clear structure and examples
- **api-documentation**: REST endpoint documentation including request/response formats, error handling, and curl examples

## Acceptance Criteria

- [ ] New "## API Reference" section added after "## Discovery Endpoints" in README.md
- [ ] Each endpoint documented with: HTTP method, URL, purpose, parameters, response schema, status codes, error format
- [ ] All three endpoints documented: `/mcp/tools/list`, `/mcp/tools/describe`, `/mcp/tools/invoke`
- [ ] Pagination mechanism for `/mcp/tools/list` clearly explained (cursor encoding/decoding)
- [ ] Security model documented (inherits from JSON-RPC access control)
- [ ] Each endpoint includes practical curl command examples
- [ ] All JSON examples are syntactically valid

Use your internal Todo tool to track these and keep on track.

## Technical Requirements

**Endpoints to Document:**

1. **GET `/mcp/tools/list`**
   - Query parameter: `cursor` (optional, base64-encoded)
   - Response: `{"tools": [...], "nextCursor": string|null}`
   - Pagination: Page size 50, base64-encoded offset cursor
   - See: McpToolsController::list (lines 72-97)

2. **GET `/mcp/tools/describe`**
   - Query parameter: `name` (required, string)
   - Response: `{"tool": {...}}` or `{"error": {...}}`
   - Error codes: 400 (missing_parameter), 404 (tool_not_found)
   - See: McpToolsController::describe (lines 111-139)

3. **POST `/mcp/tools/invoke`**
   - Request body: `{"name": string, "arguments": object}`
   - Response: `{"result": ...}` or `{"error": {...}}`
   - Error codes: 400 (invalid_json, missing_parameter), 404 (tool_not_found), 500 (execution_error)
   - See: McpToolsController::invoke (lines 154-262)

**MCP Specification Reference:**

- Version: 2025-06-18
- URL: https://modelcontextprotocol.io/specification/2025-06-18/server/tools

## Input Dependencies

- Current README.md content (preserve existing structure)
- McpToolsController.php implementation (for accurate endpoint behavior)
- MCP specification (for compliance verification)

## Output Artifacts

- Updated README.md with new "## API Reference" section
- Complete endpoint documentation with examples
- Valid JSON request/response examples

## Implementation Notes

<details>
<summary>Detailed Implementation Instructions</summary>

### Step 1: Review Source Implementation

Read `/var/www/html/web/modules/contrib/jsonrpc_mcp/src/Controller/McpToolsController.php` to understand:

- Exact request/response formats
- Error handling paths and codes
- Pagination logic (base64-encoded offset, page size 50)
- Parameter validation

### Step 2: Structure API Reference Section

Add after "## Discovery Endpoints" section in README.md:

```markdown
## API Reference

Complete technical reference for MCP discovery endpoints. All endpoints follow the [MCP Specification (2025-06-18)](https://modelcontextprotocol.io/specification/2025-06-18/server/tools).

### Security

All endpoints inherit access control from the underlying JSON-RPC methods. Authentication follows standard Drupal authentication mechanisms. Users must have the permissions specified in the `#[JsonRpcMethod]` attribute's `access` parameter.

### GET /mcp/tools/list

[Document here...]

### GET /mcp/tools/describe

[Document here...]

### POST /mcp/tools/invoke

[Document here...]
```

### Step 3: Document Each Endpoint

**For each endpoint, include:**

1. **Purpose**: One-sentence description of what it does
2. **Parameters**: Table or list with name, type, required/optional, description
3. **Request Example**: Curl command showing actual usage
4. **Response (Success)**: JSON schema with example
5. **Response (Error)**: Error format with possible error codes
6. **Status Codes**: 200, 400, 404, 500 with descriptions

**Example Format:**

````markdown
### GET /mcp/tools/list

Returns a paginated list of all available MCP tools.

**Parameters:**

- `cursor` (optional, string): Base64-encoded pagination cursor. Omit for first page.

**Request:**

```bash
# First page
curl https://your-site.com/mcp/tools/list

# Next page
curl https://your-site.com/mcp/tools/list?cursor=NTA=
```
````

**Response (200 OK):**

```json
{
  "tools": [
    {
      "name": "cache.rebuild",
      "title": "Rebuild Drupal Cache",
      "description": "Rebuilds the Drupal system cache.",
      "inputSchema": { "type": "object", "properties": {} },
      "outputSchema": { "type": "boolean" }
    }
  ],
  "nextCursor": "NTA=" // base64-encoded offset, null if last page
}
```

**Pagination:**

- Page size: 50 tools per request
- Cursor format: Base64-encoded integer offset
- Follow `nextCursor` for additional pages (null indicates last page)

**Status Codes:**

- `200 OK`: Successful request

```

### Step 4: Validate JSON Examples

- Ensure all JSON examples are valid (use `jq` or JSON validator)
- Match examples to actual McpToolsController responses
- Include realistic data (not just placeholders)

### Step 5: Cross-Reference Implementation

- Verify pagination logic matches McpToolsController::list (offset/page_size)
- Confirm error codes match controller responses
- Validate parameter names and types

### Step 6: Technical Writer Review

Use the technical-writer agent to review for:
- Clarity and readability
- Consistent terminology
- Proper balance of detail
- Professional tone
- Accessibility to different skill levels

### Quality Checklist

- [ ] All three endpoints fully documented
- [ ] JSON examples are valid and realistic
- [ ] Error codes match implementation
- [ ] Pagination mechanism clearly explained
- [ ] Security model documented
- [ ] Curl examples are copy-pasteable
- [ ] Cross-references to MCP specification included
- [ ] Technical writer approved

</details>
```
