---
id: 8
summary: 'Enhance README documentation with comprehensive MCP discovery API reference, specification links, and complete endpoint documentation for list, describe, and invoke operations'
created: 2025-10-02
---

# Plan: Improve Discovery API Documentation in README

## Original Work Order

> Alright, I need to update the documentation to formalize the code around the discovery. Right now we have quite good documentation on the readme for the annotation to specify that particular JSON-RPC method is actually an MCP tool. And we have some documentation about the discovery endpoints, but we are not linking to the specification about these discovery endpoints or giving proper API documentation on the discovery endpoints for list, describe and invoke. Your job is to improve the readme documentation and strive for completeness, correctness, but also being succinct and concise. Bear in mind that people will want to read this documentation. Use the technical writer agent to do this task.

## Executive Summary

The current README.md provides solid coverage of the `#[McpTool]` attribute usage but lacks formal API documentation for the three discovery endpoints (`/mcp/tools/list`, `/mcp/tools/describe`, `/mcp/tools/invoke`). This plan addresses documentation gaps by adding:

1. **Formal API Reference Section**: Complete endpoint documentation with request/response formats, parameters, status codes, and examples
2. **MCP Specification Links**: Direct references to the official Model Context Protocol specification (2025-06-18)
3. **Enhanced Discovery Section**: Clearer explanation of how the discovery mechanism works with concrete examples

The approach prioritizes technical accuracy while maintaining readability through clear structure, practical examples, and cross-references to the specification. The technical writer agent will ensure documentation meets professional standards for API reference materials.

## Context

### Current State

The README.md currently includes:

- Good explanation of the `#[McpTool]` attribute with code examples
- Basic mention of `/mcp/tools/list` endpoint with a simple request/response example
- Metadata mapping table showing JSON-RPC to MCP field conversions
- Development workflow examples

**Missing Elements:**

- No links to the official MCP specification (2025-06-18 revision)
- No documentation for `/mcp/tools/describe` endpoint (only code implementation exists)
- No documentation for `/mcp/tools/invoke` endpoint (only code implementation exists)
- No formal API reference section with complete request/response schemas
- No error response documentation
- No security/authentication documentation for endpoints
- No pagination details for cursor-based navigation

### Target State

Enhanced README.md that includes:

- **API Reference Section**: Comprehensive documentation for all three discovery endpoints
- **Specification Links**: Direct links to MCP specification with context
- **Complete Request/Response Schemas**: Full documentation of all parameters, status codes, error formats
- **Practical Examples**: Real-world usage examples for each endpoint
- **Security Documentation**: Clear explanation of access control and authentication
- **Pagination Guide**: Detailed explanation of cursor-based pagination mechanism

Success criteria: Developers can fully understand and implement MCP client interactions without needing to read source code.

### Background

The module implements three MCP discovery endpoints as documented in the MCP specification (2025-06-18):

1. **`/mcp/tools/list`** (McpToolsController::list): Returns paginated list of available tools with cursor-based navigation
2. **`/mcp/tools/describe`** (McpToolsController::describe): Returns detailed schema for a specific tool by name
3. **`/mcp/tools/invoke`** (McpToolsController::invoke): Executes a tool via JSON-RPC handler and returns results

The implementation follows MCP specification conventions but documentation lags behind the code implementation. The official specification can be found at: https://modelcontextprotocol.io/specification/2025-06-18/server/tools

## Technical Implementation Approach

### Component 1: API Reference Section

**Objective**: Create a comprehensive "API Reference" section that documents all discovery endpoints with complete technical specifications.

**Implementation Strategy**:

- Add new "## API Reference" section after "## Discovery Endpoints"
- Document each endpoint as a subsection with standardized structure:
  - Endpoint URL and HTTP method
  - Purpose and use case
  - Request parameters (query params or body)
  - Response format with JSON schema
  - Status codes (200, 400, 404, 500)
  - Error response format
  - Practical examples with curl commands
- Include pagination details for `/mcp/tools/list` (cursor encoding/decoding)
- Document security model (inherits from JSON-RPC access control)

**Example Structure**:

````markdown
### GET /mcp/tools/list

Lists all available MCP tools with pagination support.

**Parameters:**

- `cursor` (optional, string): Base64-encoded pagination cursor

**Response (200 OK):**

```json
{
  "tools": [...],
  "nextCursor": "base64-encoded-string" | null
}
```
````

**Error Response (4xx/5xx):**

```json
{
  "error": {
    "code": "error_code",
    "message": "Human-readable message"
  }
}
```

````

### Component 2: MCP Specification Links

**Objective**: Add clear references to the official MCP specification throughout the documentation for developers who need deeper technical details.

**Implementation Strategy**:
- Add specification link in the "Overview" section with version (2025-06-18)
- Reference specification in "How It Works" section when explaining MCP compliance
- Link to specific specification sections from API reference (e.g., tools/list, tools/call)
- Add "Further Reading" or "References" section at the end with all relevant specification links
- Use consistent link format: `[MCP Specification (2025-06-18)](https://modelcontextprotocol.io/specification/2025-06-18/server/tools)`

### Component 3: Enhanced Discovery Endpoints Section

**Objective**: Restructure and enhance the "Discovery Endpoints" section to be more informative and easier to navigate.

**Implementation Strategy**:
- Keep current basic examples but add context about when to use each endpoint
- Add workflow diagram or numbered steps showing typical discovery flow:
  1. Client queries `/mcp/tools/list` to discover available tools
  2. (Optional) Client queries `/mcp/tools/describe?name=X` for detailed schema
  3. Client invokes tool via `/mcp/tools/invoke` with JSON body
- Add note about pagination workflow (when to follow `nextCursor`)
- Cross-reference to full API Reference section for complete details

### Component 4: Technical Writer Review

**Objective**: Ensure documentation meets professional technical writing standards for clarity, accuracy, and completeness.

**Implementation Strategy**:
- Use technical-writer agent to review and refine all documentation additions
- Ensure consistent terminology throughout
- Verify all JSON examples are valid and properly formatted
- Check that documentation is accessible to both beginner and advanced developers
- Ensure proper balance between conciseness and completeness
- Validate that all code examples follow best practices

## Risk Considerations and Mitigation Strategies

### Technical Risks

- **Specification Drift**: MCP specification may evolve and documentation could become outdated
  - **Mitigation**: Explicitly version-tag specification references (2025-06-18), add note about checking official spec for latest version

- **Implementation Mismatch**: Documentation may not accurately reflect actual endpoint behavior
  - **Mitigation**: Cross-reference all documented behavior with McpToolsController.php implementation, include test examples that can be verified

### Implementation Risks

- **Documentation Bloat**: Adding too much detail could make README overwhelming
  - **Mitigation**: Use collapsible sections if needed, maintain clear hierarchy with H2/H3 headings, focus on practical usage first, then reference details

- **Incomplete Error Coverage**: May not document all possible error scenarios
  - **Mitigation**: Review McpToolsController error handling code paths, document at least major error codes (400, 404, 500)

### Quality Risks

- **Inconsistent Examples**: JSON examples may have inconsistent formatting or invalid syntax
  - **Mitigation**: Validate all JSON examples, use consistent formatting tool, ensure examples match actual API responses

## Success Criteria

### Primary Success Criteria

1. README.md contains complete API reference for all three discovery endpoints (`/mcp/tools/list`, `/mcp/tools/describe`, `/mcp/tools/invoke`)
2. Documentation includes direct links to MCP specification (2025-06-18) with proper context
3. All endpoints have documented request parameters, response formats, and error codes
4. Pagination mechanism is clearly explained with cursor encoding details

### Quality Assurance Metrics

1. Technical writer agent approves documentation for clarity and completeness
2. All JSON examples are syntactically valid and match actual endpoint responses
3. Documentation enables a developer to implement an MCP client without reading source code
4. Security and access control model is clearly explained
5. README remains concise and readable (documentation additions don't exceed 50% increase in length)

## Resource Requirements

### Development Skills

- Technical writing expertise (API documentation, REST endpoint documentation)
- Understanding of MCP specification and protocol design
- Drupal module documentation conventions
- JSON Schema knowledge
- Markdown formatting proficiency

### Technical Infrastructure

- Access to technical-writer agent for documentation review
- MCP specification reference (https://modelcontextprotocol.io)
- McpToolsController.php source code for implementation verification
- JSON validation tools for example verification

## Implementation Order

1. **Research and Reference Gathering**: Collect all MCP specification links, review McpToolsController implementation
2. **API Reference Section Creation**: Document `/mcp/tools/list`, `/mcp/tools/describe`, `/mcp/tools/invoke` with complete schemas
3. **Specification Link Integration**: Add MCP specification references throughout documentation
4. **Discovery Section Enhancement**: Restructure and improve discovery workflow explanation
5. **Technical Writer Review**: Submit to technical-writer agent for professional review and refinement
6. **Validation and Testing**: Verify all examples, validate JSON syntax, cross-check with actual endpoint behavior

## Notes

- The technical writer agent should focus on balancing completeness with readability
- Documentation should serve both developers implementing MCP clients and developers creating MCP tools in Drupal
- Consider adding a "Quick Start" callout box at the beginning of API Reference for impatient developers
- May want to add a troubleshooting section if common issues emerge during implementation
- Keep AGENTS.md constraints in mind: no AI attribution, no unnecessary file creation

## Task Dependencies

```mermaid
graph TD
    001[Task 1: Create API Reference Section] --> 003[Task 3: Enhance Discovery Workflow]
    001 --> 004[Task 4: Technical Writer Final Review]
    002[Task 2: Add MCP Specification Links] --> 004
    003 --> 004
````

## Execution Blueprint

**Validation Gates:**

- Reference: `.ai/task-manager/config/hooks/POST_PHASE.md`

### Phase 1: Core Documentation Creation

**Parallel Tasks:**

- Task 1: Create API Reference Section - Document all three discovery endpoints with complete schemas
- Task 2: Add MCP Specification Links - Integrate specification references throughout README

### Phase 2: Workflow Enhancement

**Parallel Tasks:**

- Task 3: Enhance Discovery Workflow - Restructure discovery section with clear workflow guidance (depends on: 1)

### Phase 3: Quality Assurance

**Parallel Tasks:**

- Task 4: Technical Writer Final Review - Comprehensive review and polish of all documentation (depends on: 1, 2, 3)

### Execution Summary

- Total Phases: 3
- Total Tasks: 4
- Maximum Parallelism: 2 tasks (in Phase 1)
- Critical Path Length: 3 phases
- Estimated Complexity: Low-Medium (all tasks ≤4.0 complexity score)
