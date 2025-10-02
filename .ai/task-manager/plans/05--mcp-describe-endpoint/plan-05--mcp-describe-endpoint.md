---
id: 5
summary: 'Implement /mcp/tools/describe endpoint and ensure full compliance with DevTurtle MCP A2A framework specification'
created: 2025-10-02
---

# Plan: MCP Tools Describe Endpoint Implementation

## Original Work Order

> Ensure that the MCP discovery covers the /mcp/tools/describe routes and in general complies with the API described in this post https://www.devturtleblog.com/agentic-a2a-framework-mcp/

## Executive Summary

This plan addresses the missing `/mcp/tools/describe` endpoint in the jsonrpc_mcp module, which is required for full compliance with the MCP (Model Context Protocol) A2A framework. Currently, the module implements only `/mcp/tools/list` for tool discovery. The DevTurtle blog post specifies that MCP requires three core endpoints: list, describe, and invoke. The describe endpoint provides detailed schema information for specific tools, complementing the list endpoint's overview functionality.

The implementation will add a new controller method and route to handle describe requests, extend the normalizer to support single-tool normalization with enhanced detail, and ensure the overall API structure aligns with the DevTurtle MCP specification. This minimal change maintains the existing architecture while completing the required API surface.

## Context

### Current State

The jsonrpc_mcp module currently implements:

- **`/mcp/tools/list` endpoint** (`McpToolsController::list()`) - Returns paginated list of all available MCP tools
- **McpToolNormalizer** - Converts JSON-RPC methods to MCP tool schema format
- **McpToolDiscoveryService** - Discovers JSON-RPC methods with `#[McpTool]` attribute
- **McpTool attribute** - Marks methods for MCP exposure

The routing configuration (`jsonrpc_mcp.routing.yml`) defines only one route. The module documentation (AGENTS.md) mentions the describe endpoint as missing from the implementation roadmap.

### Target State

The module will provide a complete MCP-compliant API with:

- **`/mcp/tools/describe` endpoint** - Returns detailed schema for a specific tool identified by name parameter
- Proper error handling for non-existent tools
- Consistent JSON response format matching the list endpoint structure
- Full alignment with DevTurtle MCP specification for discovery endpoints

### Background

The MCP specification defines a stateless, JSON-based protocol for AI agent tool discovery and invocation. The DevTurtle blog post clarifies the three-endpoint pattern:

1. **list** - Discover available tools
2. **describe** - Get detailed information about specific tools
3. **invoke** - Execute a tool

The jsonrpc_mcp module implements invoke functionality through the underlying jsonrpc module's `/jsonrpc` endpoint, so only the describe endpoint is missing. The describe endpoint serves as a detailed view that can include additional metadata, parameter constraints, and examples that might be too verbose for the list endpoint.

## Technical Implementation Approach

### Describe Controller Method

**Objective**: Add HTTP endpoint handler for tool description requests

The `McpToolsController` class will gain a new `describe()` method that:

- Accepts a required `name` query parameter (the tool identifier)
- Uses `McpToolDiscoveryService` to retrieve all discovered tools
- Locates the specific tool by matching the `name` parameter against tool IDs
- Returns 404 response if tool not found or user lacks access
- Returns the normalized tool schema wrapped in a consistent response structure

The method signature will follow the existing `list()` pattern:

```php
public function describe(Request $request): JsonResponse
```

Response format for successful requests:

```json
{
  "tool": {
    "name": "example.method",
    "description": "Method description",
    "inputSchema": {...},
    "outputSchema": {...}
  }
}
```

Error response for missing tools (404 status):

```json
{
  "error": {
    "code": "tool_not_found",
    "message": "Tool 'invalid.name' not found or access denied"
  }
}
```

### Routing Configuration

**Objective**: Register the describe endpoint with proper path and access controls

Add new route definition to `jsonrpc_mcp.routing.yml`:

- Path: `/mcp/tools/describe`
- Controller: `\Drupal\jsonrpc_mcp\Controller\McpToolsController::describe`
- Access: Public (`_access: 'TRUE'`) to match list endpoint
- Options: No cache (`no_cache: 'TRUE'`) for dynamic content

This maintains consistency with the existing list endpoint configuration and ensures MCP clients can discover tool details without authentication barriers (access control enforced at the per-tool level via JSON-RPC permissions).

### Normalizer Enhancement (If Needed)

**Objective**: Ensure normalizer supports describe endpoint requirements

Evaluate whether the existing `McpToolNormalizer::normalize()` method provides sufficient detail for describe responses. The current implementation already includes:

- Tool name, description, title
- Complete inputSchema with properties and required fields
- outputSchema (if defined)
- Annotations metadata

If the describe endpoint requires additional information not currently in the normalized output (e.g., examples, parameter constraints, deprecation warnings), extend the normalizer method or add a new `normalizeDetailed()` method. The McpTool attribute may also need new optional properties to support this metadata.

### Testing Strategy

**Objective**: Ensure describe endpoint functions correctly across scenarios

Add functional test coverage in `tests/src/Functional/Controller/McpToolsControllerTest.php`:

- Test successful describe request for existing tool
- Test 404 response for non-existent tool name
- Test access control (user without permissions should not see tool)
- Test response format matches expected structure
- Test that outputSchema appears when defined
- Test that annotations and title appear when provided

Use the existing jsonrpc_mcp_examples module's test methods (ListArticles, ArticleToMarkdown) as test subjects to validate real-world usage.

## Risk Considerations and Mitigation Strategies

### Technical Risks

- **Inconsistent Response Format**: The describe and list endpoints might return schemas in different formats
  - **Mitigation**: Use the same normalizer service for both endpoints, ensuring identical schema structure. Add explicit format validation in tests.

- **Performance with Large Parameter Schemas**: Some JSON-RPC methods may have complex parameter definitions that create verbose schemas
  - **Mitigation**: The describe endpoint returns single tools, so response size is bounded. No pagination needed. Consider adding response compression at the web server level if needed in production.

### Implementation Risks

- **Breaking Changes to Existing API**: Modifying the normalizer might affect list endpoint responses
  - **Mitigation**: Extend normalizer with new methods rather than modifying existing ones. Run full test suite before and after changes to catch regressions.

- **Access Control Edge Cases**: Tool might be discoverable via list but return 404 in describe if permissions change between requests
  - **Mitigation**: This is expected behavior (permissions are checked at request time). Document this in controller comments and consider adding logging for access denied cases.

## Success Criteria

### Primary Success Criteria

1. `/mcp/tools/describe` endpoint returns correct tool schema for valid tool names with 200 status
2. Endpoint returns 404 with appropriate error message for non-existent or inaccessible tools
3. Response format matches MCP specification and aligns with list endpoint structure
4. All existing tests continue to pass without modification

### Quality Assurance Metrics

1. Functional test coverage includes at least 4 test cases (success, not found, access denied, response format)
2. PHPStan static analysis passes with no new errors
3. Drupal coding standards (PHPCS) pass for all new code
4. Manual testing with curl confirms responses match specification

## Resource Requirements

### Development Skills

- **Drupal Controller Development**: Understanding of Drupal's controller architecture and JsonResponse usage
- **Drupal Routing**: Knowledge of routing.yml configuration and path/access requirements
- **PHP Reflection API**: Familiarity with attribute inspection (already used in existing code)
- **JSON Schema**: Understanding of JSON Schema Draft 7 for validation

### Technical Infrastructure

- **Existing Services**: No new services required (reuses McpToolDiscoveryService and McpToolNormalizer)
- **Testing Tools**: PHPUnit for functional tests, curl for manual endpoint testing
- **Development Environment**: Drupal 11.1 with jsonrpc module ^3.0.0-beta1

## Implementation Order

1. Add describe route definition to `jsonrpc_mcp.routing.yml`
2. Implement `describe()` method in `McpToolsController`
3. Add functional tests in `McpToolsControllerTest.php`
4. Run cache rebuild (`vendor/bin/drush cache:rebuild`)
5. Execute test suite to validate implementation
6. Perform manual testing with curl against example tools
7. Run code quality checks (PHPStan, PHPCS)

## Notes

- The `/mcp/tools/invoke` endpoint mentioned in the DevTurtle blog is handled by the existing `/jsonrpc` endpoint from the jsonrpc module - no implementation needed
- The module already includes example MCP tools in the jsonrpc_mcp_examples submodule for testing
- Consider adding documentation to AGENTS.md about the describe endpoint once implemented
- The describe endpoint does not require pagination since it returns a single tool
