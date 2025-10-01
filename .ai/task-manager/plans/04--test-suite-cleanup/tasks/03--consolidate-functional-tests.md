---
id: 3
group: 'test-cleanup'
dependencies: []
status: 'pending'
created: '2025-10-01'
skills:
  - phpunit
  - drupal-backend
---

# Consolidate Functional Tests into Single Method

## Objective

Combine all 19 functional test methods in McpToolsControllerTest.php into a single test method to eliminate the performance overhead of creating 19 separate Drupal installations.

## Skills Required

- **phpunit**: Understanding PHPUnit and Drupal functional testing
- **drupal-backend**: Knowledge of BrowserTestBase and Drupal functional test patterns

## Acceptance Criteria

- [ ] All 19 test methods consolidated into single `testMcpEndpointBehavior()` method
- [ ] All meaningful assertions preserved from original tests
- [ ] Assertions organized in logical flow with inline comments
- [ ] Conditional assertions handled (e.g., pagination tests only if nextCursor exists)
- [ ] Test passes: `vendor/bin/phpunit --group jsonrpc_mcp --testsuite=functional`
- [ ] Test execution time significantly reduced (1 Drupal installation instead of 19)

## Technical Requirements

**Files to modify:**

- `tests/src/Functional/Controller/McpToolsControllerTest.php`

**Current structure:** 19 separate test methods, each triggering full Drupal site build
**Target structure:** Single `testMcpEndpointBehavior()` method with all assertions

**Assertion categories to preserve:**

1. Endpoint accessibility (200 status code)
2. JSON response validation
3. MCP schema compliance (required fields, types)
4. Tool name/description mapping
5. InputSchema format validation
6. Pagination behavior (cursor parameter)
7. Access control filtering (anonymous vs authenticated)
8. Optional MCP fields (title, outputSchema, annotations)
9. Content-Type header
10. Unmarked methods exclusion

## Input Dependencies

None - this task can start immediately

## Output Artifacts

- Consolidated `McpToolsControllerTest.php` with single test method
- Preserved test coverage with 95%+ reduction in Drupal installation overhead

## Implementation Notes

<details>
<summary>Detailed implementation guidance</summary>

### Consolidation Strategy

**Preserve all meaningful assertions from these 19 methods:**

1. `testToolsListEndpointExists()`
2. `testToolsListReturnsJson()`
3. `testToolsListStructure()`
4. `testToolSchemaCompliance()`
5. `testToolNameMapping()`
6. `testToolDescriptionMapping()`
7. `testInputSchemaFormat()`
8. `testPaginationWithCursor()`
9. `testPaginationNextCursor()`
10. `testPaginationLastPage()`
11. `testDiscoveryIncludesTestMethod()`
12. `testEmptyToolsForRestrictedUser()`
13. `testAccessControlFiltering()`
14. `testOptionalMcpFields()`
15. `testResponseContentType()`
16. `testUnmarkedMethodsExcluded()`
    17-19. Other variations

### Target Structure

```php
class McpToolsControllerTest extends BrowserTestBase {

  protected static $modules = [
    'system',
    'user',
    'jsonrpc',
    'jsonrpc_mcp',
    'jsonrpc_mcp_test', // Will be replaced in task 4
  ];

  protected $defaultTheme = 'stark';

  public function testMcpEndpointBehavior(): void {
    // Section 1: Anonymous user tests
    // - Test endpoint exists and returns 200
    // - Test returns valid JSON
    // - Test response structure (tools array, nextCursor)
    // - Test MCP schema compliance
    // - Test unmarked methods excluded
    // - Test admin-only tools not visible

    // Section 2: Authenticated user with permissions
    // - Create user with 'administer site configuration'
    // - Login
    // - Test admin-only tools now visible
    // - Test access control filtering works

    // Section 3: Detailed schema validation
    // - Test tool name mapping (id → name)
    // - Test description mapping (usage → description)
    // - Test inputSchema format (type, properties, required)
    // - Test optional fields (title, outputSchema, annotations)
    // - Test Content-Type header

    // Section 4: Pagination (if applicable)
    // - Test cursor parameter handling
    // - Test nextCursor generation
    // - Follow pagination to last page
    // - Verify null nextCursor on last page
  }
}
```

### Execution Steps

1. Open `tests/src/Functional/Controller/McpToolsControllerTest.php`
2. Create new method `testMcpEndpointBehavior()`
3. Copy assertions from each of the 19 methods into appropriate sections
4. Add inline comments to mark logical sections
5. Handle conditional logic (e.g., pagination only if nextCursor !== null)
6. Delete the 19 original test methods
7. Run test: `vendor/bin/phpunit tests/src/Functional/Controller/McpToolsControllerTest.php`
8. Fix any ordering issues or assertion failures
9. Verify test passes

### Tips for Consolidation

1. **Preserve assertion order**: Keep assertions in roughly the same order as original tests to avoid surprises
2. **Use helper variables**: Extract `$data = json_decode(...)` once, reuse throughout
3. **Comment sections**: Add `// Section: Endpoint Accessibility` style comments for readability
4. **Handle conditionals**: Use `if ($data['nextCursor'] !== null) { ... }` for pagination tests
5. **User context switching**: Create and login users in the middle of the test for access control checks

### Performance Impact

**Current:** 19 test methods × ~30 seconds per Drupal install = ~9.5 minutes setup time
**After:** 1 test method × ~30 seconds = ~30 seconds setup time
**Savings:** ~95% reduction in functional test overhead

</details>
