---
id: 7
group: 'testing'
dependencies: [5]
status: 'pending'
created: '2025-10-02'
skills:
  - phpunit
  - drupal-backend
---

# Add Functional Tests for Invoke Endpoint

## Objective

Add comprehensive functional test coverage for the `/mcp/tools/invoke` endpoint, including successful execution, permission enforcement via JSON-RPC, request validation, error handling, and response format verification.

## Skills Required

- **phpunit**: Experience writing Drupal functional tests with POST requests and JSON body handling
- **drupal-backend**: Understanding of JSON-RPC permission model and Drupal content creation for test data

## Acceptance Criteria

- [ ] Tests added to `tests/src/Functional/Controller/McpToolsControllerTest.php`
- [ ] Test successful tool invocation with valid parameters
- [ ] Test JSON-RPC permission enforcement (403/error when lacking method permissions)
- [ ] Test 404 response for non-existent tool
- [ ] Test 400 response for malformed requests (missing name, invalid arguments, invalid JSON)
- [ ] Test response format validation (result structure)
- [ ] Test error handling for execution failures
- [ ] Test parameter validation (required parameters)
- [ ] All tests pass with `vendor/bin/phpunit --group jsonrpc_mcp`

Use your internal Todo tool to track these and keep on track.

## Technical Requirements

**Test Class Location**: `tests/src/Functional/Controller/McpToolsControllerTest.php`

**Test Methods to Add**:

1. `testInvokeEndpointSuccess()` - Valid invocation with permissions
2. `testInvokeEndpointPermissionDenied()` - Without JSON-RPC method permissions
3. `testInvokeEndpointToolNotFound()` - Invalid tool name
4. `testInvokeEndpointMalformedRequest()` - Missing name or arguments
5. `testInvokeEndpointInvalidJson()` - Malformed JSON body
6. `testInvokeEndpointResponseFormat()` - Validate response structure
7. `testInvokeEndpointParameterValidation()` - Missing required parameters

**Test Data**: Use jsonrpc_mcp_examples tools and create test content (articles, content types)

## Input Dependencies

- Task 5: invoke() endpoint implementation
- Existing test infrastructure
- jsonrpc_mcp_examples module with test tools
- Test content (articles) for tools to operate on

## Output Artifacts

- Updated `tests/src/Functional/Controller/McpToolsControllerTest.php` with 7 new test methods

## Implementation Notes

<details>
<summary>Detailed Implementation Steps</summary>

**IMPORTANT**: Copy this guideline into your implementation:

## Meaningful Test Strategy Guidelines

Your critical mantra for test generation is: "write a few tests, mostly integration".

**Focus**: Test the invoke endpoint's MCP-to-JSON-RPC translation logic and error handling. Don't test JSON-RPC execution internals (framework functionality).

---

1. **Helper method for POST requests**:

   ```php
   /**
    * Helper to make POST request to invoke endpoint.
    */
   protected function invokeToolPost(string $name, array $arguments) {
     $url = Url::fromRoute('jsonrpc_mcp.tools_invoke')->toString();
     $options = [
       'headers' => ['Content-Type' => 'application/json'],
       'body' => json_encode([
         'name' => $name,
         'arguments' => $arguments,
       ]),
     ];

     return $this->getSession()->getDriver()->getClient()->request('POST', $url, $options);
   }
   ```

2. **Test 1: Successful invocation**:

   ```php
   /**
    * Tests invoke endpoint successfully executes tool.
    */
   public function testInvokeEndpointSuccess() {
     // Create user with necessary permissions.
     // Note: list_content_types requires 'administer content types' permission.
     $user = $this->drupalCreateUser(['administer content types']);
     $this->drupalLogin($user);

     // Invoke tool that lists content types.
     $response = $this->invokeToolPost('jsonrpc_mcp_examples.list_content_types', []);

     $this->assertEquals(200, $response->getStatusCode());

     $data = json_decode($response->getContent(), TRUE);

     // Assert MCP response format.
     $this->assertArrayHasKey('result', $data);
     $this->assertIsArray($data['result']);
   }
   ```

3. **Test 2: Permission denied (JSON-RPC level)**:

   ```php
   /**
    * Tests invoke endpoint enforces JSON-RPC method permissions.
    */
   public function testInvokeEndpointPermissionDenied() {
     // Create user WITHOUT required JSON-RPC method permission.
     $user = $this->drupalCreateUser([]);
     $this->drupalLogin($user);

     // Attempt to invoke tool requiring 'administer content types'.
     $response = $this->invokeToolPost('jsonrpc_mcp_examples.list_content_types', []);

     // Should get error response (exact status depends on JSON-RPC handler).
     // May be 403, 500, or 200 with error object.
     $data = json_decode($response->getContent(), TRUE);

     // Assert error response format.
     $this->assertArrayHasKey('error', $data);
     $this->assertArrayHasKey('code', $data['error']);
     $this->assertArrayHasKey('message', $data['error']);
   }
   ```

4. **Test 3: Tool not found**:

   ```php
   /**
    * Tests invoke endpoint returns 404 for non-existent tool.
    */
   public function testInvokeEndpointToolNotFound() {
     $user = $this->drupalCreateUser([]);
     $this->drupalLogin($user);

     $response = $this->invokeToolPost('nonexistent.tool', []);

     $this->assertEquals(404, $response->getStatusCode());

     $data = json_decode($response->getContent(), TRUE);

     $this->assertArrayHasKey('error', $data);
     $this->assertEquals('tool_not_found', $data['error']['code']);
   }
   ```

5. **Test 4: Malformed request (missing fields)**:

   ```php
   /**
    * Tests invoke endpoint returns 400 for malformed requests.
    */
   public function testInvokeEndpointMalformedRequest() {
     $user = $this->drupalCreateUser([]);
     $this->drupalLogin($user);

     // Test missing 'name' field.
     $url = Url::fromRoute('jsonrpc_mcp.tools_invoke')->toString();
     $response = $this->getSession()->getDriver()->getClient()->request('POST', $url, [
       'headers' => ['Content-Type' => 'application/json'],
       'body' => json_encode(['arguments' => []]),
     ]);

     $this->assertEquals(400, $response->getStatusCode());

     // Test missing 'arguments' field.
     $response = $this->getSession()->getDriver()->getClient()->request('POST', $url, [
       'headers' => ['Content-Type' => 'application/json'],
       'body' => json_encode(['name' => 'test.tool']),
     ]);

     $this->assertEquals(400, $response->getStatusCode());
   }
   ```

6. **Test 5: Invalid JSON**:

   ```php
   /**
    * Tests invoke endpoint returns 400 for invalid JSON.
    */
   public function testInvokeEndpointInvalidJson() {
     $user = $this->drupalCreateUser([]);
     $this->drupalLogin($user);

     $url = Url::fromRoute('jsonrpc_mcp.tools_invoke')->toString();
     $response = $this->getSession()->getDriver()->getClient()->request('POST', $url, [
       'headers' => ['Content-Type' => 'application/json'],
       'body' => '{invalid json}',
     ]);

     $this->assertEquals(400, $response->getStatusCode());

     $data = json_decode($response->getContent(), TRUE);
     $this->assertArrayHasKey('error', $data);
     $this->assertEquals('invalid_json', $data['error']['code']);
   }
   ```

7. **Test 6: Response format validation**:

   ```php
   /**
    * Tests invoke endpoint response follows MCP specification.
    */
   public function testInvokeEndpointResponseFormat() {
     $user = $this->drupalCreateUser(['administer content types']);
     $this->drupalLogin($user);

     $response = $this->invokeToolPost('jsonrpc_mcp_examples.list_content_types', []);

     $data = json_decode($response->getContent(), TRUE);

     // Successful response should have 'result' key.
     $this->assertArrayHasKey('result', $data);
     $this->assertIsArray($data['result']);

     // Should NOT have 'error' key on success.
     $this->assertArrayNotHasKey('error', $data);
   }
   ```

8. **Test 7: Parameter validation**:

   ```php
   /**
    * Tests invoke endpoint validates required parameters.
    */
   public function testInvokeEndpointParameterValidation() {
     // Create test article node for article_to_markdown tool.
     $node = $this->drupalCreateNode([
       'type' => 'article',
       'title' => 'Test Article',
       'body' => ['value' => 'Test content'],
     ]);

     $user = $this->drupalCreateUser(['access content']);
     $this->drupalLogin($user);

     // article_to_markdown requires 'nid' parameter.
     // Test with missing parameter.
     $response = $this->invokeToolPost('jsonrpc_mcp_examples.article_to_markdown', []);

     // Should get error from JSON-RPC about missing parameter.
     $data = json_decode($response->getContent(), TRUE);
     $this->assertArrayHasKey('error', $data);

     // Test with valid parameter.
     $response = $this->invokeToolPost('jsonrpc_mcp_examples.article_to_markdown', [
       'nid' => $node->id(),
     ]);

     // Should succeed.
     $this->assertEquals(200, $response->getStatusCode());
     $data = json_decode($response->getContent(), TRUE);
     $this->assertArrayHasKey('result', $data);
   }
   ```

9. **Test execution commands**:

   ```bash
   # Run all jsonrpc_mcp tests
   vendor/bin/phpunit --group jsonrpc_mcp

   # Run only invoke tests (if properly named with pattern)
   vendor/bin/phpunit --filter testInvoke tests/src/Functional/Controller/McpToolsControllerTest.php
   ```

10. **Common pitfalls**:
    - Not understanding JSON-RPC permission model (different from Drupal permissions)
    - Forgetting Content-Type header for POST requests
    - Not creating test content (articles, content types) for tools to operate on
    - Assuming specific error status codes (JSON-RPC may vary)
    - Not handling both 'result' and 'error' response formats
    - Using GET instead of POST

11. **Permission model notes**:
    - Invoke endpoint is public at routing level (`_access: 'TRUE'`)
    - JSON-RPC handler enforces method-specific permissions
    - Example: `list_content_types` requires `'administer content types'` permission
    - Example: `list_articles` may require `'access content'` permission
    - Check jsonrpc_mcp_examples method definitions for required permissions

12. **Test coverage focus**: - Request validation (YOUR code) - MCP format translation (YOUR code) - Error handling (YOUR code) - Don't test JSON-RPC execution internals (framework code) - Integration tests confirm full request/response cycle works
</details>
