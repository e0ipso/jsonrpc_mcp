---
id: 3
group: 'controller-implementation'
dependencies: [2]
status: 'completed'
created: '2025-10-02'
skills:
  - drupal-backend
  - api-endpoints
---

# Implement Describe Endpoint in McpToolsController

## Objective

Implement the `describe()` method in `McpToolsController` to handle `/mcp/tools/describe` requests, returning detailed MCP-compliant schema for a specific tool identified by the `name` query parameter.

## Skills Required

- **drupal-backend**: Understanding of Drupal controller architecture, dependency injection, and service usage
- **api-endpoints**: Knowledge of HTTP request handling, query parameters, JSON responses, and RESTful API design

## Acceptance Criteria

- [ ] `describe()` method added to `McpToolsController` class
- [ ] Method accepts `Request` object and returns `JsonResponse`
- [ ] Extracts `name` query parameter from request
- [ ] Returns 404 with error structure if tool not found
- [ ] Returns 200 with tool schema wrapped in `{"tool": {...}}` structure for valid tools
- [ ] Uses existing `McpToolDiscoveryService` and `McpToolNormalizer` services
- [ ] Error responses follow MCP format with `error.code` and `error.message`
- [ ] Method signature: `public function describe(Request $request): JsonResponse`

Use your internal Todo tool to track these and keep on track.

## Technical Requirements

**Method Signature:**

```php
public function describe(Request $request): JsonResponse
```

**Success Response Format (200):**

```json
{
  "tool": {
    "name": "example.method",
    "description": "Method description",
    "inputSchema": {...},
    "outputSchema": {...},
    "title": "Optional title",
    "annotations": {...}
  }
}
```

**Error Response Format (404):**

```json
{
  "error": {
    "code": "tool_not_found",
    "message": "Tool 'invalid.name' not found or access denied"
  }
}
```

**Implementation Logic:**

1. Extract `name` query parameter: `$request->query->get('name')`
2. Call `$this->toolDiscovery->discoverTools()` to get all accessible tools
3. Search for tool with matching ID in the returned array
4. If not found, return 404 JsonResponse with error structure
5. If found, normalize using `$this->normalizer->normalize($method)`
6. Wrap normalized tool in `{"tool": ...}` structure and return JsonResponse

## Input Dependencies

- Task 2: Route must be defined before controller method can be called
- Existing `McpToolDiscoveryService` (already implemented)
- Existing `McpToolNormalizer` (already implemented)

## Output Artifacts

- Updated `src/Controller/McpToolsController.php` with new `describe()` method

## Implementation Notes

<details>
<summary>Detailed Implementation Steps</summary>

1. **Open controller file**:
   - Location: `/var/www/html/web/modules/contrib/jsonrpc_mcp/src/Controller/McpToolsController.php`
   - Review existing `list()` method for pattern consistency

2. **Add describe() method after list() method**:

   ```php
   /**
    * Returns detailed MCP-compliant tool description.
    *
    * Handles the /mcp/tools/describe endpoint, returning detailed schema
    * for a specific tool identified by the 'name' query parameter.
    *
    * @param \Symfony\Component\HttpFoundation\Request $request
    *   The HTTP request object.
    *
    * @return \Symfony\Component\HttpFoundation\JsonResponse
    *   JSON response with 'tool' object or 'error' object.
    */
   public function describe(Request $request): JsonResponse {
     $name = $request->query->get('name');

     if (!$name) {
       return new JsonResponse([
         'error' => [
           'code' => 'missing_parameter',
           'message' => 'Required parameter "name" is missing',
         ],
       ], 400);
     }

     $tools = $this->toolDiscovery->discoverTools();

     if (!isset($tools[$name])) {
       return new JsonResponse([
         'error' => [
           'code' => 'tool_not_found',
           'message' => sprintf("Tool '%s' not found or access denied", $name),
         ],
       ], 404);
     }

     $normalized_tool = $this->normalizer->normalize($tools[$name]);

     return new JsonResponse([
       'tool' => $normalized_tool,
     ]);
   }
   ```

3. **Key implementation details**:
   - **Query parameter extraction**: Use `$request->query->get('name')` (not `$request->get('name')`)
   - **Array key lookup**: Tools array is keyed by method ID, so use `isset($tools[$name])`
   - **Error structure**: Always include both `code` (machine-readable) and `message` (human-readable)
   - **HTTP status codes**: 400 for missing parameter, 404 for not found, 200 for success

4. **Access control**:
   - Permission check handled by routing layer (`_permission: 'access mcp tool discovery'`)
   - Per-tool access control handled by `McpToolDiscoveryService::discoverTools()` (filters inaccessible tools)
   - No additional access checks needed in controller

5. **Error handling considerations**:
   - Missing `name` parameter: Return 400 (bad request)
   - Tool not found: Return 404 (could be non-existent or user lacks permission)
   - Normalize errors: Should not occur (normalizer handles all MethodInterface objects)

6. **Response wrapping**:
   - Success: Wrap in `{"tool": ...}` (singular, one tool)
   - Error: Use `{"error": ...}` structure
   - Consistent with MCP specification format

7. **Testing after implementation**:

   ```bash
   # Test with valid tool (requires permission)
   curl -u admin:admin "https://drupal-site/mcp/tools/describe?name=jsonrpc_mcp_examples.list_articles"

   # Test with invalid tool
   curl -u admin:admin "https://drupal-site/mcp/tools/describe?name=nonexistent.tool"

   # Test without name parameter
   curl -u admin:admin "https://drupal-site/mcp/tools/describe"
   ```

8. **Common pitfalls**:
   - Do not use `$request->get('name')` - this checks POST body first
   - Do not forget to validate `$name` is not empty/null
   - Ensure error messages are descriptive but don't leak sensitive information
   - Use sprintf() for variable interpolation in error messages
   </details>
