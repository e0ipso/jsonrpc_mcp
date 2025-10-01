---
id: 8
group: 'mcp-discovery-endpoint'
dependencies: [3, 5, 9]
status: 'pending'
created: '2025-10-01'
'skills: ['drupal-functional-testing', 'http-testing']
---

# Create Functional Tests for McpToolsController

## Objective

Implement browser-based functional tests for the `/mcp/tools/list` HTTP endpoint, testing the full request/response cycle including JSON format and pagination.

## Skills Required

- Drupal functional testing (BrowserTestBase, drupalGet, JSON assertions)
- HTTP endpoint testing (testing routes, query parameters, response formats)

## Acceptance Criteria

- [ ] File `tests/src/Functional/Controller/McpToolsControllerTest.php` exists
- [ ] Test class extends `BrowserTestBase`
- [ ] Uses `@group jsonrpc_mcp` annotation
- [ ] Declares `$modules` including required dependencies
- [ ] Tests endpoint returns valid JSON
- [ ] Tests response structure matches MCP specification
- [ ] Tests pagination with cursor parameter
- [ ] Tests empty result set
- [ ] Tests access control filtering
- [ ] All tests pass: `vendor/bin/phpunit --group jsonrpc_mcp tests/src/Functional/Controller/`

## Technical Requirements

**File Location:** `tests/src/Functional/Controller/McpToolsControllerTest.php`

**Test Coverage Requirements:**

1. **Basic Endpoint Tests:**
   - `testToolsListEndpointExists()`: Verify route is accessible
   - `testToolsListReturnsJson()`: Verify JSON response
   - `testToolsListStructure()`: Verify response has `tools` and `nextCursor` keys

2. **Response Format Tests:**
   - `testToolSchemaCompliance()`: Verify each tool has required MCP fields
   - `testToolNameMapping()`: Verify id→name transformation
   - `testToolDescriptionMapping()`: Verify usage→description transformation
   - `testInputSchemaFormat()`: Verify inputSchema is valid JSON Schema

3. **Pagination Tests:**
   - `testPaginationWithCursor()`: Test cursor parameter
   - `testPaginationNextCursor()`: Verify nextCursor generation
   - `testPaginationLastPage()`: Verify null nextCursor on last page

4. **Integration Tests:**
   - `testDiscoveryIncludesTestMethod()`: Verify test plugin appears
   - `testEmptyResponse()`: No tools when none are defined

**Test Pattern:**

```php
namespace Drupal\Tests\jsonrpc_mcp\Functional\Controller;

use Drupal\Tests\BrowserTestBase;

/**
 * @group jsonrpc_mcp
 */
class McpToolsControllerTest extends BrowserTestBase {

  protected static $modules = [
    'system',
    'user',
    'jsonrpc',
    'jsonrpc_mcp',
    'jsonrpc_mcp_test',
  ];

  protected $defaultTheme = 'stark';

  /**
   * Tests the tools list endpoint returns valid JSON.
   */
  public function testToolsListReturnsJson(): void {
    $this->drupalGet('/mcp/tools/list');
    $this->assertSession()->statusCodeEquals(200);

    $response = $this->getSession()->getPage()->getContent();
    $data = json_decode($response, TRUE);

    $this->assertIsArray($data);
    $this->assertArrayHasKey('tools', $data);
    $this->assertArrayHasKey('nextCursor', $data);
  }

  /**
   * Tests tool schema MCP compliance.
   */
  public function testToolSchemaCompliance(): void {
    $this->drupalGet('/mcp/tools/list');
    $data = json_decode($this->getSession()->getPage()->getContent(), TRUE);

    foreach ($data['tools'] as $tool) {
      $this->assertArrayHasKey('name', $tool);
      $this->assertArrayHasKey('description', $tool);
      $this->assertArrayHasKey('inputSchema', $tool);
    }
  }

  // Additional test methods...
}
```

## Input Dependencies

- Task 003 completed (McpToolsController exists)
- Task 005 completed (Route defined)
- Task 009 completed (Test plugin exists for testing)

## Output Artifacts

- `tests/src/Functional/Controller/McpToolsControllerTest.php` - Complete functional test suite
- Verification that full HTTP stack works correctly

## Implementation Notes

- Functional tests use real HTTP requests via Symfony browser kit
- Tests run with full Drupal installation
- Use `drupalGet()` to make HTTP requests
- Parse JSON response with `json_decode()`
- Test with both anonymous and authenticated users
- Verify MCP specification compliance (version 2025-06-18)
