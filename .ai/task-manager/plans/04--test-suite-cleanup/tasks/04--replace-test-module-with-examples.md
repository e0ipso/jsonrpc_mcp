---
id: 4
group: 'test-cleanup'
dependencies: [1, 2, 3]
status: 'completed'
created: '2025-10-01'
skills:
  - drupal-backend
---

# Replace Test Module with Examples Submodule

## Objective

Replace the jsonrpc_mcp_test module with the jsonrpc_mcp_examples submodule in all test dependencies, update test assertions to reference example methods instead of test methods, and delete the redundant test module.

## Skills Required

- **drupal-backend**: Understanding Drupal module dependencies and test module configuration

## Acceptance Criteria

- [ ] All test files updated to use `jsonrpc_mcp_examples` in `$modules` array
- [ ] Test assertions updated to reference example methods (e.g., `list.contentTypes` instead of `test.example`)
- [ ] Test module `tests/modules/jsonrpc_mcp_test/` deleted
- [ ] All tests pass: `vendor/bin/phpunit --group jsonrpc_mcp`
- [ ] No references to `jsonrpc_mcp_test` remain in codebase

## Technical Requirements

**Test module being replaced:**

- `tests/modules/jsonrpc_mcp_test/` - Contains 4 test method plugins

**Replacement submodule:**

- `modules/jsonrpc_mcp_examples/` - Contains production-ready example methods

**Method mapping:**

- `test.example` → `list.contentTypes` (or similar from examples)
- `test.adminOnly` → Example method with admin permissions
- `test.authenticated` → Example method with authenticated access
- `test.unmarked` → No longer needed (can create unmarked stub if required)

**Files to modify:**

- `tests/src/Unit/Normalizer/McpToolNormalizerTest.php` (if it references test methods)
- `tests/src/Kernel/Service/McpToolDiscoveryServiceTest.php` (uses test methods)
- `tests/src/Functional/Controller/McpToolsControllerTest.php` (uses test methods)

## Input Dependencies

**Depends on tasks:** 1, 2, 3

- Unit tests reduced (may reference test methods in fixtures)
- Kernel tests reduced (uses test methods for discovery)
- Functional tests consolidated (references test methods in assertions)

## Output Artifacts

- Updated test files using `jsonrpc_mcp_examples` module
- Deleted `tests/modules/jsonrpc_mcp_test/` directory
- All tests passing with examples submodule

## Implementation Notes

<details>
<summary>Detailed implementation guidance</summary>

### Step 1: Identify Example Method Mapping

First, examine the example methods available:

```bash
ls -la modules/jsonrpc_mcp_examples/src/Plugin/jsonrpc/Method/
```

Expected methods:

- `ListContentTypes.php` - Lists Drupal content types
- `ListArticles.php` - Lists article nodes
- `ArticleToMarkdown.php` - Exports articles as markdown

Determine which example method to use for each test scenario:

- Need a method accessible to all → Use `list.contentTypes` (or check its permissions)
- Need a method requiring admin permissions → Check which example has admin permissions
- Need an unmarked method → May need to keep one test method or create a fixture class

### Step 2: Update Test Module Dependencies

In each test file, change:

```php
protected static $modules = [
  'system',
  'user',
  'jsonrpc',
  'jsonrpc_mcp',
  'jsonrpc_mcp_test', // REMOVE THIS
];
```

To:

```php
protected static $modules = [
  'system',
  'user',
  'jsonrpc',
  'jsonrpc_mcp',
  'jsonrpc_mcp_examples', // ADD THIS
  'node', // May be needed by examples
  'field', // May be needed by examples
];
```

**Files to update:**

- `tests/src/Kernel/Service/McpToolDiscoveryServiceTest.php`
- `tests/src/Functional/Controller/McpToolsControllerTest.php`

### Step 3: Update Test Assertions

Find all references to test methods and update:

**Kernel tests:**

```php
// OLD
$this->assertArrayHasKey('test.example', $tools);
$this->assertArrayHasKey('test.adminOnly', $tools);
$this->assertArrayNotHasKey('test.unmarked', $tools);

// NEW
$this->assertArrayHasKey('list.contentTypes', $tools);
$this->assertArrayHasKey('[admin-example-method]', $tools);
// May need to create unmarked fixture if no unmarked method exists
```

**Functional tests:**

```php
// OLD
if ($tool['name'] === 'test.example') { ... }
$this->assertContains('test.example', $tool_names);
$this->assertNotContains('test.adminOnly', $tool_names);

// NEW
if ($tool['name'] === 'list.contentTypes') { ... }
$this->assertContains('list.contentTypes', $tool_names);
$this->assertNotContains('[admin-method]', $tool_names);
```

### Step 4: Handle Edge Cases

**Unmarked method test:**
If tests require a method WITHOUT McpTool attribute (test.unmarked), you have two options:

1. Create a minimal fixture class in tests/src/Fixtures/ with JsonRpcMethod but no McpTool
2. Remove the test if it's no longer needed after test reduction

**Permission-based tests:**
Verify that jsonrpc_mcp_examples has methods with different permission levels:

- Public/authenticated access
- Admin-only access

If not, you may need to add McpTool attributes to existing example methods.

### Step 5: Delete Test Module

```bash
rm -rf tests/modules/jsonrpc_mcp_test/
```

Verify no references remain:

```bash
grep -r "jsonrpc_mcp_test" tests/
grep -r "test\.example" tests/
grep -r "test\.adminOnly" tests/
```

### Step 6: Run Tests

```bash
# Install examples submodule dependencies
vendor/bin/drush pm:enable jsonrpc_mcp_examples node field

# Run all tests
vendor/bin/phpunit --group jsonrpc_mcp

# Verify each suite
vendor/bin/phpunit --group jsonrpc_mcp --testsuite=unit
vendor/bin/phpunit --group jsonrpc_mcp --testsuite=kernel
vendor/bin/phpunit --group jsonrpc_mcp --testsuite=functional
```

### Execution Order

1. Check available example methods and their permissions
2. Plan method mapping (test.example → list.contentTypes, etc.)
3. Update module dependencies in test files
4. Update all test assertions referencing test methods
5. Handle unmarked method test (create fixture or remove)
6. Delete test module directory
7. Run tests and fix any failures
8. Verify no references to jsonrpc_mcp_test remain

</details>
