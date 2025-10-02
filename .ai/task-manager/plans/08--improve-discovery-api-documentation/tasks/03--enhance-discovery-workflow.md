---
id: 3
group: 'api-documentation'
dependencies: [1]
status: 'pending'
created: '2025-10-02'
skills:
  - technical-writing
---

# Enhance Discovery Endpoints Section with Workflow

## Objective

Restructure and enhance the "Discovery Endpoints" section in README.md to provide clearer explanation of the discovery workflow, including when to use each endpoint and how they work together.

## Skills Required

- **technical-writing**: Clear procedural documentation with workflow explanation and step-by-step guidance

## Acceptance Criteria

- [ ] "Discovery Endpoints" section restructured with clearer workflow explanation
- [ ] Numbered steps or diagram showing typical discovery flow (list → describe → invoke)
- [ ] Context added about when to use each endpoint
- [ ] Pagination workflow explained (when to follow `nextCursor`)
- [ ] Cross-references to API Reference section added
- [ ] Current basic examples preserved but enhanced with context

Use your internal Todo tool to track these and keep on track.

## Technical Requirements

**Discovery Flow:**

1. Client queries `/mcp/tools/list` to discover available tools (with pagination if needed)
2. (Optional) Client queries `/mcp/tools/describe?name=X` for detailed schema of specific tool
3. Client invokes tool via `/mcp/tools/invoke` with JSON body containing name and arguments

**Pagination Workflow:**

- Check `nextCursor` in `/mcp/tools/list` response
- If not null, make additional request with `cursor` parameter
- Continue until `nextCursor` is null

**Cross-Reference:**

- Link to "## API Reference" section for complete endpoint details

## Input Dependencies

- Task 1 output (completed API Reference section)
- Current "Discovery Endpoints" section in README.md
- Understanding of typical MCP client workflow

## Output Artifacts

- Updated "## Discovery Endpoints" section with workflow explanation
- Clear guidance on endpoint usage patterns
- Cross-references to detailed API documentation

## Implementation Notes

<details>
<summary>Detailed Implementation Instructions</summary>

### Step 1: Review Current Discovery Endpoints Section

Read the existing "## Discovery Endpoints" section in README.md to understand:

- Current content and examples
- What's missing (workflow, context, pagination details)
- What should be preserved

### Step 2: Restructure Section with Workflow

Replace or enhance current section with:

````markdown
## Discovery Endpoints

The module provides three complementary endpoints for MCP tool discovery and invocation. A typical workflow follows this pattern:

### Discovery Workflow

1. **List Available Tools** - Query `/mcp/tools/list` to get all accessible tools
   - Supports pagination for sites with many tools
   - Returns tool names and basic metadata
   - Follow `nextCursor` for additional pages

2. **Get Detailed Schema** (Optional) - Query `/mcp/tools/describe?name=<tool-name>` for full schema
   - Useful for dynamic form generation or validation
   - Returns complete inputSchema and outputSchema
   - Only needed if `/mcp/tools/list` doesn't provide sufficient detail

3. **Invoke Tool** - POST to `/mcp/tools/invoke` with tool name and arguments
   - Executes tool via JSON-RPC handler
   - Returns results or error information
   - Requires proper authentication and permissions

### Pagination Example

When dealing with many tools, use cursor-based pagination:

```bash
# First request
curl https://your-site.com/mcp/tools/list

# Response includes nextCursor
{
  "tools": [...],
  "nextCursor": "NTA="
}

# Follow nextCursor for more results
curl https://your-site.com/mcp/tools/list?cursor=NTA=

# Continue until nextCursor is null
{
  "tools": [...],
  "nextCursor": null  // Last page
}
```
````

> 💡 **See the [API Reference](#api-reference) section below for complete endpoint documentation including request/response formats, error codes, and detailed examples.**

````

### Step 3: Add "When to Use Each Endpoint" Section

```markdown
### Endpoint Usage Guidance

**Use `/mcp/tools/list` when:**
- Initially discovering what tools are available
- Building a tool catalog or index
- Checking for tool availability before invocation
- The basic metadata (name, title, description) is sufficient

**Use `/mcp/tools/describe` when:**
- You need complete inputSchema or outputSchema details
- Building dynamic forms or validation logic
- The summary from `/mcp/tools/list` lacks needed detail
- Generating client code or documentation

**Use `/mcp/tools/invoke` when:**
- Actually executing a tool
- You have the tool name and prepared arguments
- User/system is ready to perform the action
````

### Step 4: Update Existing Examples

Enhance current basic example with context:

````markdown
### Quick Example

**List available tools:**

```bash
curl https://your-site.com/mcp/tools/list | jq
```
````

**Response:**

```json
{
  "tools": [
    {
      "name": "cache.rebuild",
      "title": "Rebuild Drupal Cache",
      "description": "Rebuilds the Drupal system cache.",
      "inputSchema": {
        "type": "object",
        "properties": {}
      },
      "outputSchema": {
        "type": "boolean"
      },
      "annotations": {
        "category": "system",
        "destructive": false
      }
    }
  ],
  "nextCursor": null
}
```

**Describe specific tool:**

```bash
curl "https://your-site.com/mcp/tools/describe?name=cache.rebuild" | jq
```

**Invoke the tool:**

```bash
curl -X POST https://your-site.com/mcp/tools/invoke \
  -H "Content-Type: application/json" \
  -d '{"name": "cache.rebuild", "arguments": {}}'
```

**Response:**

```json
{
  "result": true
}
```

````

### Step 5: Cross-Reference API Documentation

Add clear navigation to detailed docs:

```markdown
---

**📚 For complete API documentation** including all parameters, status codes, error formats, and security details, see the [API Reference](#api-reference) section below.
````

### Step 6: Technical Writer Review

Use technical-writer agent to:

- Verify workflow is clear and logical
- Check examples are practical and realistic
- Ensure guidance helps developers choose right endpoint
- Validate pagination explanation is understandable
- Confirm cross-references are helpful

### Quality Checklist

- [ ] Discovery workflow clearly explained (3-step pattern)
- [ ] Pagination workflow documented with example
- [ ] "When to use" guidance provided for each endpoint
- [ ] Current examples preserved and enhanced
- [ ] Cross-references to API Reference section added
- [ ] Examples use realistic data and proper formatting
- [ ] Technical writer approved

</details>
