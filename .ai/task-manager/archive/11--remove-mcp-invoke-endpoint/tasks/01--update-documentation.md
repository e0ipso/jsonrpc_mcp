---
id: 1
group: 'documentation'
dependencies: []
status: 'completed'
created: '2025-10-28'
skills:
  - markdown
  - technical-writing
---

# Update Documentation to Reference JSON-RPC Endpoint

## Objective

Update README.md and AGENTS.md to remove all references to `/mcp/tools/invoke` and add comprehensive guidance on using the standard `/jsonrpc` endpoint for tool execution. This clarifies the new architecture where MCP endpoints are for discovery only.

## Skills Required

- **markdown**: Edit and structure documentation files
- **technical-writing**: Create clear API examples and usage guidance

## Acceptance Criteria

- [ ] README.md no longer references `/mcp/tools/invoke` endpoint
- [ ] AGENTS.md caching documentation updated to remove invoke endpoint
- [ ] New section added explaining JSON-RPC 2.0 tool execution
- [ ] Curl examples updated to show `/jsonrpc` endpoint usage
- [ ] Architecture diagram updated to reflect discovery-only role
- [ ] Documentation spell check passes: `npm run cspell:check`

Use your internal Todo tool to track these and keep on track.

## Technical Requirements

**README.md changes:**

- Remove `/mcp/tools/invoke` from architecture diagram (around line 30)
- Update "API Reference" section (around line 372):
  - Remove "POST /mcp/tools/invoke" section
  - Add "Tool Execution via JSON-RPC" section
- Update curl examples (around line 229) to use `/jsonrpc` with JSON-RPC 2.0 format
- Update workflow guidance (around line 170, 188) to reference JSON-RPC endpoint

**AGENTS.md changes:**

- Update caching documentation (line 78) to remove invoke endpoint reference
- Update any workflow diagrams mentioning `/mcp/tools/invoke`

**New content to add:**

- Section explaining JSON-RPC 2.0 request format:
  ```json
  {
    "jsonrpc": "2.0",
    "method": "examples.contentTypes.list",
    "params": {},
    "id": "unique-request-id"
  }
  ```
- Example curl command for `/jsonrpc` endpoint
- Clarification that MCP endpoints are for discovery, execution via JSON-RPC
- Link to JSON-RPC module documentation

## Input Dependencies

None - this is the first task per implementation order.

## Output Artifacts

- Updated README.md with JSON-RPC execution guidance
- Updated AGENTS.md with corrected caching documentation
- Documentation passing spell check validation

## Implementation Notes

<details>
<summary>Detailed implementation guidance</summary>

### Step 1: Locate and remove invoke references

Search for all occurrences of "invoke" in documentation:

```bash
grep -n "invoke" README.md AGENTS.md
```

Remove or replace references to `/mcp/tools/invoke` endpoint.

### Step 2: Add JSON-RPC execution section

Add a new section after the API Reference for list/describe:

**Title**: "Tool Execution via JSON-RPC Endpoint"

**Content structure**:

1. Brief explanation: "After discovering tools via MCP endpoints, execute them using the standard `/jsonrpc` endpoint"
2. JSON-RPC 2.0 request format explanation
3. Example request showing method, params, id fields
4. Example curl command
5. Link to drupal/jsonrpc module documentation

### Step 3: Update architecture diagrams

Current diagram shows three endpoints. Update to show:

- `/mcp/tools/list` → Discovery
- `/mcp/tools/describe` → Schema details
- `/jsonrpc` → Execution (external to this module)

### Step 4: Validate documentation

Run spell check:

```bash
npm run cspell:check
```

Fix any spelling issues introduced by the changes.

### Example JSON-RPC execution section

````markdown
## Tool Execution

After discovering tools via the MCP discovery endpoints, execute them using the standard `/jsonrpc` endpoint provided by the JSON-RPC module.

### JSON-RPC 2.0 Request Format

```json
POST /jsonrpc
Content-Type: application/json

{
  "jsonrpc": "2.0",
  "method": "examples.contentTypes.list",
  "params": {},
  "id": "request-123"
}
```
````

### Example: Execute content types list

```bash
curl -X POST https://your-site.com/jsonrpc \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0",
    "method": "examples.contentTypes.list",
    "params": {},
    "id": "1"
  }'
```

For complete JSON-RPC documentation, see the [JSON-RPC module](https://www.drupal.org/project/jsonrpc).

```

</details>
```
