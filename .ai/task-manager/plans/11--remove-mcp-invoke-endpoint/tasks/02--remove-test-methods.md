---
id: 2
group: 'test-removal'
dependencies: []
status: 'completed'
created: '2025-10-28'
skills:
  - phpunit
  - drupal-testing
---

# Remove Invoke Endpoint Test Methods

## Objective

Remove the 5 test methods for the `/mcp/tools/invoke` endpoint from McpToolsControllerTest.php while preserving all tests for the list and describe endpoints. Ensure the test suite remains functional and passing after removal.

## Skills Required

- **phpunit**: Understand PHPUnit test structure and dependencies
- **drupal-testing**: Work with Drupal functional test patterns

## Acceptance Criteria

- [ ] `testInvokeEndpointSuccess()` method removed
- [ ] `testInvokeEndpointToolNotFound()` method removed
- [ ] `testInvokeEndpointMalformedRequest()` method removed
- [ ] `testInvokeEndpointInvalidJson()` method removed (note: there's a 5th test method to remove)
- [ ] All remaining test methods preserved (list, describe, permission tests)
- [ ] Class docblock updated if it references invoke testing
- [ ] Test suite passes: `vendor/bin/phpunit --group jsonrpc_mcp`

Use your internal Todo tool to track these and keep on track.

## Technical Requirements

**File**: `tests/src/Functional/Controller/McpToolsControllerTest.php`

**Test methods to remove** (lines 318-428):

1. `testInvokeEndpointSuccess()` - lines 318-344
2. `testInvokeEndpointToolNotFound()` - lines 346-373
3. `testInvokeEndpointMalformedRequest()` - lines 375-402
4. `testInvokeEndpointInvalidJson()` - lines 404-428

**Preserve these test methods**:

- `testMcpEndpointBehavior()` - comprehensive list endpoint tests
- `testListEndpointPermissionDenied()` - permission tests
- `testDescribeEndpointSuccess()` - describe endpoint tests
- `testDescribeEndpointPermissionDenied()`
- `testDescribeEndpointToolNotFound()`
- `testDescribeEndpointMissingParameter()`

## Input Dependencies

None - test removal can happen independently of code changes.

## Output Artifacts

- Updated McpToolsControllerTest.php with invoke tests removed
- Passing test suite confirming no shared setup was broken

## Implementation Notes

<details>
<summary>Detailed implementation guidance</summary>

### Step 1: Read the test file

Read the entire test file to understand the structure:

```bash
cat tests/src/Functional/Controller/McpToolsControllerTest.php
```

Identify the exact line numbers for each test method to remove.

### Step 2: Remove test methods

Use the Edit tool to remove each test method. The methods are:

1. **testInvokeEndpointSuccess()** - Tests successful tool execution via invoke endpoint
2. **testInvokeEndpointToolNotFound()** - Tests 404 response for non-existent tool
3. **testInvokeEndpointMalformedRequest()** - Tests 400 response for missing parameters
4. **testInvokeEndpointInvalidJson()** - Tests 400 response for invalid JSON

Remove the entire method including docblock, method signature, and body.

### Step 3: Update class docblock

Check the class-level docblock (around line 11-18):

```php
/**
 * Functional tests for the MCP tools discovery endpoint.
 *
 * Tests the /mcp/tools/list HTTP endpoint including JSON format, MCP
 * compliance, pagination, and access control integration.
 *
 * @group jsonrpc_mcp
 */
```

If it mentions invoke endpoint testing, update it to only reference list and describe endpoints.

### Step 4: Verify no shared dependencies

Check the setUp() method and any helper methods to ensure nothing was shared between invoke tests and other tests. The setUp() method should be preserved as-is since it's used by list/describe tests.

### Step 5: Run test suite

After removal, run the full test suite to ensure remaining tests still pass:

```bash
vendor/bin/phpunit --group jsonrpc_mcp
```

If any tests fail, investigate whether there were shared fixtures or setup code that needs to be preserved.

### Step 6: Verify test count

Before removal, count test methods:

```bash
grep -c "public function test" tests/src/Functional/Controller/McpToolsControllerTest.php
```

After removal, verify the count decreased by 4.

</details>
