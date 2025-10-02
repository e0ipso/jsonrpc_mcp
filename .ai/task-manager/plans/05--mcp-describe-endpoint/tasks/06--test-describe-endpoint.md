---
id: 6
group: 'testing'
dependencies: [3]
status: 'pending'
created: '2025-10-02'
skills:
  - phpunit
  - drupal-backend
---

# Add Functional Tests for Describe Endpoint

## Objective

Add comprehensive functional test coverage for the `/mcp/tools/describe` endpoint, including permission-based access control, tool discovery, error handling, and response format validation.

## Skills Required

- **phpunit**: Experience writing Drupal functional tests with BrowserTestBase
- **drupal-backend**: Understanding of Drupal's permission system, user creation, and test patterns

## Acceptance Criteria

- [ ] Tests added to existing `tests/src/Functional/Controller/McpToolsControllerTest.php`
- [ ] Test successful describe request with proper permission
- [ ] Test 403 response when user lacks "access mcp tool discovery" permission
- [ ] Test 404 response for non-existent tool name
- [ ] Test response format validation (tool structure, fields present)
- [ ] Test outputSchema appears when defined
- [ ] Test annotations and title appear when provided
- [ ] All tests pass with `vendor/bin/phpunit --group jsonrpc_mcp`

Use your internal Todo tool to track these and keep on track.

## Technical Requirements

**Test Class Location**: `tests/src/Functional/Controller/McpToolsControllerTest.php`

**Test Methods to Add**:

1. `testDescribeEndpointSuccess()` - Valid tool with permission
2. `testDescribeEndpointPermissionDenied()` - Without permission
3. `testDescribeEndpointToolNotFound()` - Invalid tool name
4. `testDescribeEndpointResponseFormat()` - Validate response structure
5. `testDescribeEndpointOutputSchema()` - Verify outputSchema included
6. `testDescribeEndpointAnnotations()` - Verify title and annotations

**Test Data Sources**:

- Use `jsonrpc_mcp_examples` module tools: `list_articles`, `article_to_markdown`, `list_content_types`
- These tools have #[McpTool] attributes for testing

## Input Dependencies

- Task 3: describe() endpoint implementation
- Existing test infrastructure in `McpToolsControllerTest.php`
- jsonrpc_mcp_examples module with test tools

## Output Artifacts

- Updated `tests/src/Functional/Controller/McpToolsControllerTest.php` with 6 new test methods

## Implementation Notes

<details>
<summary>Detailed Implementation Steps</summary>

**IMPORTANT**: Copy this guideline into your implementation:

## Meaningful Test Strategy Guidelines

Your critical mantra for test generation is: "write a few tests, mostly integration".

**Definition of "Meaningful Tests":**
Tests that verify custom business logic, critical paths, and edge cases specific to the application. Focus on testing YOUR code, not the framework or library functionality.

**When TO Write Tests:**

- Custom business logic and algorithms
- Critical user workflows and data transformations
- Edge cases and error conditions for core functionality
- Integration points between different system components
- Complex validation logic or calculations

**When NOT to Write Tests:**

- Third-party library functionality (already tested upstream)
- Framework features (React hooks, Express middleware, etc.)
- Simple CRUD operations without custom logic
- Getter/setter methods or basic property access
- Configuration files or static data
- Obvious functionality that would break immediately if incorrect

---

1. **Review existing test file**:
   - Location: `/var/www/html/web/modules/contrib/jsonrpc_mcp/tests/src/Functional/Controller/McpToolsControllerTest.php`
   - Review test patterns for list endpoint
   - Use similar setup/teardown patterns

2. **Test 1: Success case with permission**:

   ```php
   /**
    * Tests describe endpoint returns tool schema for valid tool name.
    */
   public function testDescribeEndpointSuccess() {
     // Create user with permission.
     $user = $this->drupalCreateUser(['access mcp tool discovery']);
     $this->drupalLogin($user);

     // Request tool description.
     $this->drupalGet('/mcp/tools/describe', [
       'query' => ['name' => 'jsonrpc_mcp_examples.list_content_types'],
     ]);

     $this->assertSession()->statusCodeEquals(200);

     // Decode response.
     $response = json_decode($this->getSession()->getPage()->getContent(), TRUE);

     // Assert response structure.
     $this->assertArrayHasKey('tool', $response);
     $this->assertArrayHasKey('name', $response['tool']);
     $this->assertArrayHasKey('description', $response['tool']);
     $this->assertArrayHasKey('inputSchema', $response['tool']);

     // Assert tool name matches request.
     $this->assertEquals('jsonrpc_mcp_examples.list_content_types', $response['tool']['name']);
   }
   ```

3. **Test 2: Permission denied**:

   ```php
   /**
    * Tests describe endpoint returns 403 without permission.
    */
   public function testDescribeEndpointPermissionDenied() {
     // Create user WITHOUT permission.
     $user = $this->drupalCreateUser([]);
     $this->drupalLogin($user);

     // Attempt to access describe endpoint.
     $this->drupalGet('/mcp/tools/describe', [
       'query' => ['name' => 'jsonrpc_mcp_examples.list_content_types'],
     ]);

     $this->assertSession()->statusCodeEquals(403);
   }
   ```

4. **Test 3: Tool not found**:

   ```php
   /**
    * Tests describe endpoint returns 404 for non-existent tool.
    */
   public function testDescribeEndpointToolNotFound() {
     $user = $this->drupalCreateUser(['access mcp tool discovery']);
     $this->drupalLogin($user);

     $this->drupalGet('/mcp/tools/describe', [
       'query' => ['name' => 'nonexistent.tool'],
     ]);

     $this->assertSession()->statusCodeEquals(404);

     $response = json_decode($this->getSession()->getPage()->getContent(), TRUE);

     $this->assertArrayHasKey('error', $response);
     $this->assertArrayHasKey('code', $response['error']);
     $this->assertEquals('tool_not_found', $response['error']['code']);
   }
   ```

5. **Test 4: Response format validation**:

   ```php
   /**
    * Tests describe endpoint response follows MCP specification.
    */
   public function testDescribeEndpointResponseFormat() {
     $user = $this->drupalCreateUser(['access mcp tool discovery']);
     $this->drupalLogin($user);

     $this->drupalGet('/mcp/tools/describe', [
       'query' => ['name' => 'jsonrpc_mcp_examples.list_articles'],
     ]);

     $response = json_decode($this->getSession()->getPage()->getContent(), TRUE);

     // Validate required fields.
     $this->assertArrayHasKey('tool', $response);
     $tool = $response['tool'];

     $this->assertArrayHasKey('name', $tool);
     $this->assertArrayHasKey('description', $tool);
     $this->assertArrayHasKey('inputSchema', $tool);

     // Validate inputSchema structure.
     $this->assertArrayHasKey('type', $tool['inputSchema']);
     $this->assertEquals('object', $tool['inputSchema']['type']);
     $this->assertArrayHasKey('properties', $tool['inputSchema']);
   }
   ```

6. **Test 5: OutputSchema presence**:

   ```php
   /**
    * Tests describe endpoint includes outputSchema when defined.
    */
   public function testDescribeEndpointOutputSchema() {
     $user = $this->drupalCreateUser(['access mcp tool discovery']);
     $this->drupalLogin($user);

     // Use tool that defines outputSchema (article_to_markdown).
     $this->drupalGet('/mcp/tools/describe', [
       'query' => ['name' => 'jsonrpc_mcp_examples.article_to_markdown'],
     ]);

     $response = json_decode($this->getSession()->getPage()->getContent(), TRUE);
     $tool = $response['tool'];

     // Assert outputSchema is present.
     $this->assertArrayHasKey('outputSchema', $tool);
     $this->assertIsArray($tool['outputSchema']);
   }
   ```

7. **Test 6: Annotations and title**:

   ```php
   /**
    * Tests describe endpoint includes title and annotations from McpTool attribute.
    */
   public function testDescribeEndpointAnnotations() {
     $user = $this->drupalCreateUser(['access mcp tool discovery']);
     $this->drupalLogin($user);

     $this->drupalGet('/mcp/tools/describe', [
       'query' => ['name' => 'jsonrpc_mcp_examples.list_articles'],
     ]);

     $response = json_decode($this->getSession()->getPage()->getContent(), TRUE);
     $tool = $response['tool'];

     // If tool has title in McpTool attribute, it should appear.
     // Check examples module to see which tools have title/annotations.
     if (isset($tool['title'])) {
       $this->assertIsString($tool['title']);
     }

     if (isset($tool['annotations'])) {
       $this->assertIsArray($tool['annotations']);
     }
   }
   ```

8. **Module dependencies**:
   - Ensure test class has: `protected static $modules = ['jsonrpc_mcp', 'jsonrpc_mcp_examples'];`
   - Examples module provides test tools with various schema configurations

9. **Test execution**:

   ```bash
   # Run all jsonrpc_mcp tests
   vendor/bin/phpunit --group jsonrpc_mcp

   # Run specific test file
   vendor/bin/phpunit tests/src/Functional/Controller/McpToolsControllerTest.php
   ```

10. **Common pitfalls**:
    - Forgetting to create user with proper permission
    - Not logging in before making request
    - Using wrong query parameter syntax (use `['query' => ['name' => 'value']]`)
    - Not decoding JSON response before assertions
    - Assuming all tools have outputSchema (it's optional)
    - Not checking if optional fields exist before asserting their values

11. **Coverage notes**: - These 6 tests cover: success, permission, not found, format, outputSchema, annotations - Focus on integration testing (actual HTTP requests) - Don't test framework functionality (Drupal permission system works) - Test YOUR code: describe() method logic and response formatting
</details>
