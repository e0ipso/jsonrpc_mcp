---
id: 7
group: 'invocation'
dependencies: [6]
status: 'completed'
created: 2025-11-03
skills:
  - drupal-backend
  - php
---

# Add GET Request Support with Query Parameter

## Objective

Extend `McpToolInvokeController` to accept GET requests with JSON-RPC payload in URL-encoded `query` parameter, following the same pattern as the jsonrpc module.

## Skills Required

- **drupal-backend**: Symfony Request handling, query parameters
- **php**: JSON decoding, URL parameter handling

## Acceptance Criteria

- [ ] Controller handles both GET and POST requests
- [ ] GET requests read JSON-RPC payload from `query` parameter
- [ ] Query parameter value is URL-decoded and parsed as JSON
- [ ] Same validation and delegation logic applies to GET requests
- [ ] Returns appropriate errors for missing or invalid query parameter

## Technical Requirements

**Modify** `src/Controller/McpToolInvokeController.php`:
- Detect request method (`$request->getMethod()`)
- For GET: extract payload from `$request->query->get('query')`
- For POST: extract payload from `$request->getContent()` (existing logic)
- Merge both paths into same validation/delegation flow

**GET request format**:
```
/mcp/tools/cache.rebuild?query=%7B%22jsonrpc%22%3A%222.0%22%2C%22method%22%3A%22cache.rebuild%22%2C%22params%22%3A%7B%7D%2C%22id%22%3A%221%22%7D
```

Where the query parameter contains URL-encoded JSON:
```json
{"jsonrpc":"2.0","method":"cache.rebuild","params":{},"id":"1"}
```

## Input Dependencies

- Task 6 complete (full POST invocation with OAuth2 works)
- Routes already configured to accept GET method (from task 2)

## Output Artifacts

- Modified `McpToolInvokeController.php` supporting GET requests
- Both GET and POST requests work with same authentication/authorization logic

<details>
<summary>Implementation Notes</summary>

**Refactor `invoke()` method** to extract payload based on HTTP method:

```php
public function invoke(Request $request, string $tool_name): Response {
  // Load tool metadata and check authentication (existing code)...

  // Extract JSON-RPC payload based on HTTP method
  if ($request->getMethod() === 'GET') {
    $query_payload = $request->query->get('query');

    if (!$query_payload) {
      return new JsonResponse([
        'jsonrpc' => '2.0',
        'error' => [
          'code' => -32600,
          'message' => 'Invalid Request: Missing query parameter',
        ],
        'id' => NULL,
      ], 400);
    }

    try {
      $rpc_payload = Json::decode($query_payload);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'jsonrpc' => '2.0',
        'error' => [
          'code' => -32700,
          'message' => 'Parse error',
        ],
        'id' => NULL,
      ], 400);
    }
  }
  else {
    // POST request - existing logic
    $content = $request->getContent();
    try {
      $rpc_payload = Json::decode($content);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'jsonrpc' => '2.0',
        'error' => [
          'code' => -32700,
          'message' => 'Parse error',
        ],
        'id' => NULL,
      ], 400);
    }
  }

  // Rest of validation and delegation (existing code)...
}
```

**Testing GET Requests**:
```bash
# URL-encode the JSON payload
PAYLOAD='{"jsonrpc":"2.0","method":"cache.rebuild","params":{},"id":"1"}'
ENCODED=$(echo -n "$PAYLOAD" | jq -sRr @uri)

# Test GET request
curl -i "http://localhost/mcp/tools/cache.rebuild?query=$ENCODED" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**URL Encoding**:
- JSON payload must be URL-encoded before putting in query string
- PHP's `$request->query->get('query')` automatically URL-decodes the value
- No manual decoding needed - just parse JSON directly

**Consistency with JSON-RPC Module**:
This follows the exact same pattern as drupal/jsonrpc module's GET support. Reference: https://www.drupal.org/project/jsonrpc documentation.

**Error Handling**:
- Missing `query` parameter: Return 400 with "Invalid Request: Missing query parameter"
- Invalid JSON in query: Return 400 with "Parse error"
- All other errors (invalid structure, etc.): Same as POST handling

**Performance Note**:
GET requests with very long/complex payloads may hit URL length limits (typically 2048 characters). This is acceptable - recommend POST for complex requests.
</details>
