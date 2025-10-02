---
id: 2
group: 'api-documentation'
dependencies: []
status: 'completed'
created: '2025-10-02'
skills:
  - technical-writing
---

# Add MCP Specification Links Throughout Documentation

## Objective

Integrate direct references to the official Model Context Protocol specification (2025-06-18 revision) throughout the README.md, providing developers with authoritative sources for deeper technical understanding.

## Skills Required

- **technical-writing**: Professional documentation with appropriate cross-referencing and citation practices

## Acceptance Criteria

- [ ] Specification link added to "Overview" section with version (2025-06-18)
- [ ] Specification referenced in "How It Works" section when explaining MCP compliance
- [ ] Specific specification sections linked from API reference endpoints
- [ ] "References" or "Further Reading" section added at end of README
- [ ] Consistent link format used throughout: `[MCP Specification (2025-06-18)](https://modelcontextprotocol.io/specification/2025-06-18/server/tools)`
- [ ] All specification links verified to be accessible

Use your internal Todo tool to track these and keep on track.

## Technical Requirements

**Official MCP Specification:**

- Base URL: https://modelcontextprotocol.io/specification/2025-06-18/server/tools
- Version to cite: 2025-06-18
- Key sections: tools/list, tools/call (for invoke)

**Link Placement Guidelines:**

- Overview section: Introduce MCP with specification reference
- How It Works: Reference when explaining MCP compliance
- API Reference: Link specific endpoint docs to specification sections
- End of document: Comprehensive references section

## Input Dependencies

- Current README.md content
- MCP specification URL structure
- Task 1 output (API Reference section)

## Output Artifacts

- Updated README.md with specification links integrated
- New "## References" section at end of document
- Consistent citation format throughout

## Implementation Notes

<details>
<summary>Detailed Implementation Instructions</summary>

### Step 1: Add Specification Reference to Overview

In the "## Overview" section, update the first paragraph:

```markdown
## Overview

The Model Context Protocol (MCP) is an [open standard introduced by Anthropic](https://modelcontextprotocol.io/specification/2025-06-18/server/tools) that enables AI systems to discover and interact with external tools and data sources. This module bridges Drupal's JSON-RPC infrastructure with MCP, allowing Drupal sites to be discovered and used as MCP servers.
```

### Step 2: Update "How It Works" Section

Add specification reference when explaining MCP compliance:

```markdown
### Architecture

...

The module uses PHP 8 attributes to mark JSON-RPC methods for MCP exposure. When an MCP client queries the discovery endpoint, the module:

1. Discovers all JSON-RPC methods marked with `#[McpTool]`
2. Converts JSON-RPC metadata to MCP tool schema format per the [MCP Specification (2025-06-18)](https://modelcontextprotocol.io/specification/2025-06-18/server/tools)
3. Returns MCP-compliant tool definitions with proper JSON Schema
```

### Step 3: Link from API Reference Endpoints

In the API Reference section (from Task 1), add specification links:

```markdown
### GET /mcp/tools/list

Returns a paginated list of all available MCP tools per the [tools/list specification](https://modelcontextprotocol.io/specification/2025-06-18/server/tools#list).

...

### POST /mcp/tools/invoke

Invokes an MCP tool following the [tools/call specification](https://modelcontextprotocol.io/specification/2025-06-18/server/tools#call).
```

### Step 4: Create References Section

Add at the end of README.md, before "## License":

```markdown
## References

### MCP Specification

- [Model Context Protocol (2025-06-18)](https://modelcontextprotocol.io/specification/2025-06-18/server/tools) - Official specification
- [Tools Discovery](https://modelcontextprotocol.io/specification/2025-06-18/server/tools#list) - tools/list endpoint
- [Tool Invocation](https://modelcontextprotocol.io/specification/2025-06-18/server/tools#call) - tools/call endpoint

### Related Documentation

- [JSON-RPC Drupal Module](https://www.drupal.org/project/jsonrpc)
- [Anthropic MCP Introduction](https://www.anthropic.com/news/model-context-protocol)
```

### Step 5: Standardize Link Format

Ensure all specification links use consistent format:

- Version included: `(2025-06-18)`
- Descriptive text: `[MCP Specification (2025-06-18)](...)`
- For specific sections: `[tools/list specification](...#list)`

### Step 6: Verify Links

Test all specification URLs:

```bash
# Verify each URL returns 200 OK
curl -I https://modelcontextprotocol.io/specification/2025-06-18/server/tools
curl -I https://modelcontextprotocol.io/specification/2025-06-18/server/tools#list
# etc.
```

### Step 7: Technical Writer Review

Use technical-writer agent to:

- Verify citation consistency
- Check link placement is natural and helpful
- Ensure references section is complete
- Validate that links enhance understanding without cluttering

### Quality Checklist

- [ ] Overview section introduces MCP with specification link
- [ ] How It Works references spec when explaining compliance
- [ ] API Reference endpoints link to specific spec sections
- [ ] References section comprehensive and well-organized
- [ ] All links use consistent format with version
- [ ] Links verified accessible (200 OK)
- [ ] Technical writer approved

</details>
