---
id: 5
group: 'controller-implementation'
dependencies: [4]
status: 'completed'
created: '2025-10-02'
skills:
  - drupal-backend
  - api-endpoints
---

# Implement Invoke Endpoint with JSON-RPC Integration

## Objective

Implement the `invoke()` method in `McpToolsController` to handle `/mcp/tools/invoke` POST requests, translating MCP-format requests to JSON-RPC format, executing tools via the JSON-RPC handler, and returning MCP-format responses.

## Skills Required

- **drupal-backend**: Understanding of Drupal request handling, JSON-RPC module integration, and error handling
- **api-endpoints**: Knowledge of POST request parsing, JSON body handling, HTTP status codes, and API error responses

## Acceptance Criteria

- [ ] `invoke()` method added to `McpToolsController` class
- [ ] Method parses JSON POST body with `name` and `arguments` fields
- [ ] Validates tool exists and is accessible
- [ ] Constructs JSON-RPC request format from MCP request
- [ ] Forwards request to JSON-RPC handler using injected `HandlerInterface`
- [ ] Translates JSON-RPC response to MCP format
- [ ] Returns appropriate HTTP status codes (200, 400, 404, 500)
- [ ] Error responses follow MCP format with structured error objects
- [ ] Preserves JSON-RPC error information in MCP error responses

Use your internal Todo tool to track these and keep on track.

## Technical Requirements

**Method Signature:**

```php
public function invoke(Request $request): JsonResponse
```

**MCP Request Format (POST body):**

```json
{
  "name": "example.method",
  "arguments": {
    "param1": "value1",
    "param2": "value2"
  }
}
```

**MCP Success Response (200):**

```json
{
  "result": {
    "field1": "value1",
    "field2": "value2"
  }
}
```

**MCP Error Responses:**

- 400: Malformed request (missing name/arguments)
- 404: Tool not found
- 500: Execution error (JSON-RPC failure)

**Error Format:**

```json
{
  "error": {
    "code": "error_type",
    "message": "Descriptive error message"
  }
}
```

## Input Dependencies

- Task 4: `HandlerInterface` must be injected into controller
- JSON-RPC handler service (`jsonrpc.handler`)
- Existing `McpToolDiscoveryService` for tool validation

## Output Artifacts

- Updated `src/Controller/McpToolsController.php` with new `invoke()` method

## Implementation Notes

<details>
<summary>Detailed Implementation Steps</summary>

1. **Study JSON-RPC handler interface**:
   - Review `/var/www/html/web/modules/contrib/jsonrpc/src/HandlerInterface.php`
   - Key method: `execute()` - executes JSON-RPC requests
   - Understand JSON-RPC request/response object structure
   - Note: JSON-RPC uses `method` and `params`, MCP uses `name` and `arguments`

2. **Parse POST request body**:

   ```php
   $content = $request->getContent();
   $data = json_decode($content, TRUE);

   if (json_last_error() !== JSON_ERROR_NONE) {
     return new JsonResponse([
       'error' => [
         'code' => 'invalid_json',
         'message' => 'Request body must be valid JSON',
       ],
     ], 400);
   }
   ```

3. **Validate request structure**:

   ```php
   if (!isset($data['name']) || !is_string($data['name'])) {
     return new JsonResponse([
       'error' => [
         'code' => 'missing_parameter',
         'message' => 'Required parameter "name" is missing or invalid',
       ],
     ], 400);
   }

   if (!isset($data['arguments']) || !is_array($data['arguments'])) {
     return new JsonResponse([
       'error' => [
         'code' => 'missing_parameter',
         'message' => 'Required parameter "arguments" is missing or invalid',
       ],
     ], 400);
   }

   $name = $data['name'];
   $arguments = $data['arguments'];
   ```

4. **Validate tool exists**:

   ```php
   $tools = $this->toolDiscovery->discoverTools();

   if (!isset($tools[$name])) {
     return new JsonResponse([
       'error' => [
         'code' => 'tool_not_found',
         'message' => sprintf("Tool '%s' not found or access denied", $name),
       ],
     ], 404);
   }
   ```

5. **Execute via JSON-RPC handler**:
   - Study existing jsonrpc module controller to understand handler usage
   - Create JSON-RPC request object
   - Call `$this->handler->execute()` or equivalent
   - Handle exceptions from JSON-RPC execution

6. **Example JSON-RPC execution pattern**:

   ```php
   try {
     // The JSON-RPC handler expects a specific request format
     // Study jsonrpc module's controller for exact pattern
     // Likely involves creating a request object with method and params

     // Translate MCP format to JSON-RPC format
     $jsonrpc_request = [
       'method' => $name,
       'params' => $arguments,
       'id' => uniqid('mcp_', TRUE),
       'jsonrpc' => '2.0',
     ];

     // Execute via handler (exact API depends on HandlerInterface)
     $response = $this->handler->execute($jsonrpc_request);

     // Extract result from JSON-RPC response
     // JSON-RPC response typically has 'result' or 'error' field
     if (isset($response['result'])) {
       return new JsonResponse([
         'result' => $response['result'],
       ]);
     }

     if (isset($response['error'])) {
       return new JsonResponse([
         'error' => [
           'code' => 'execution_error',
           'message' => $response['error']['message'] ?? 'Tool execution failed',
         ],
       ], 500);
     }
   }
   catch (\Exception $e) {
     return new JsonResponse([
       'error' => [
         'code' => 'execution_error',
         'message' => sprintf('Tool execution failed: %s', $e->getMessage()),
       ],
     ], 500);
   }
   ```

7. **IMPORTANT - Research JSON-RPC handler usage**:
   - Before implementing, read `/var/www/html/web/modules/contrib/jsonrpc/src/Controller/HttpController.php`
   - Understand how the jsonrpc module processes requests
   - The handler interface may differ from assumptions above
   - Match the exact pattern used by jsonrpc module

8. **Error translation strategy**:
   - **JSON-RPC errors** → MCP error format
   - Preserve error message from JSON-RPC
   - Use consistent error codes: `execution_error`, `permission_denied`, etc.
   - Include original error context when helpful

9. **HTTP status code mapping**:
   - 200: Successful execution (even if tool returns error data)
   - 400: Malformed MCP request (invalid JSON, missing fields)
   - 404: Tool not found in discovery
   - 500: JSON-RPC execution failure or internal error

10. **Testing after implementation**:

    ```bash
    # Test successful invocation
    curl -X POST -H "Content-Type: application/json" \
      -u admin:admin \
      -d '{"name":"jsonrpc_mcp_examples.list_content_types","arguments":{}}' \
      https://drupal-site/mcp/tools/invoke

    # Test with invalid tool
    curl -X POST -H "Content-Type: application/json" \
      -u admin:admin \
      -d '{"name":"nonexistent.tool","arguments":{}}' \
      https://drupal-site/mcp/tools/invoke

    # Test with malformed JSON
    curl -X POST -H "Content-Type: application/json" \
      -u admin:admin \
      -d '{invalid json}' \
      https://drupal-site/mcp/tools/invoke
    ```

11. **Common pitfalls**:
    - Assuming JSON-RPC handler API without reading jsonrpc module code
    - Not validating JSON decode success
    - Not checking array types (arguments must be array)
    - Forgetting to handle JSON-RPC exceptions
    - Not translating JSON-RPC error format to MCP format
    - Using GET instead of POST (route enforces POST via `_method`)

12. **Access control**: - Route is public (`_access: 'TRUE'`) - JSON-RPC handler enforces method-level permissions - Tool discovery filters inaccessible tools - No additional permission checks needed in controller - JSON-RPC will return permission errors if user lacks access
</details>
