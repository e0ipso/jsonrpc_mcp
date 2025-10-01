---
id: 2
group: 'test-cleanup'
dependencies: []
status: 'pending'
created: '2025-10-01'
skills:
  - phpunit
  - drupal-backend
---

# Reduce Kernel Tests to Essential Coverage

## Objective

Remove redundant access control tests and type verification tests from McpToolDiscoveryServiceTest.php while preserving tests that validate core discovery functionality and McpTool attribute filtering.

## Skills Required

- **phpunit**: Understanding PHPUnit and Drupal kernel testing
- **drupal-backend**: Knowledge of Drupal plugin discovery and access control

## Acceptance Criteria

- [ ] McpToolDiscoveryServiceTest.php reduced from 10 to ~4 test methods
- [ ] Removed type verification tests (MethodInterface checks redundant with type hints)
- [ ] Removed redundant permission tests (multiple variations of same logic)
- [ ] Retained basic discovery functionality test
- [ ] Retained McpTool attribute filtering test
- [ ] Retained one representative access control test
- [ ] Retained test for excluding unmarked methods
- [ ] All remaining tests pass: `vendor/bin/phpunit --group jsonrpc_mcp --testsuite=kernel`

## Technical Requirements

**Files to modify:**

- `tests/src/Kernel/Service/McpToolDiscoveryServiceTest.php`

**Kernel tests use:**

- Drupal\KernelTests\KernelTestBase
- User creation traits for access control testing
- jsonrpc_mcp_test module for test methods

## Input Dependencies

None - this task can start immediately

## Output Artifacts

- Streamlined `McpToolDiscoveryServiceTest.php` with ~4 meaningful test methods

## Implementation Notes

<details>
<summary>Detailed implementation guidance</summary>

### McpToolDiscoveryServiceTest.php Reduction Strategy

**KEEP these test methods (~4 total):**

1. `testDiscoverToolsFindsMarkedMethods()` - validates discovery of methods with McpTool attribute
2. `testDiscoverToolsExcludesUnmarkedMethods()` - validates filtering (unmarked methods excluded)
3. `testDiscoverToolsRespectsPermissions()` OR `testDiscoverToolsWithAuthenticated()` - pick ONE representative access control test
4. `testDiscoveryWithMultiplePlugins()` - validates discovery with multiple MCP-marked methods

**REMOVE these test methods (~6 total):**

- `testDiscoverToolsReturnsMethodInterface()` - Type verification is redundant (return type already enforced by type hint)
- `testDiscoverToolsWithAnonymous()` - Redundant with other access control tests
- `testEmptyResultWhenNoAccessibleTools()` - Low value test with vague assertions
- `testDiscoveryWithAdminUser()` - Redundant with permission test
- `testDiscoveryPreservesMethodIds()` - Trivial test (array key preservation)
- Either `testDiscoverToolsRespectsPermissions()` OR `testDiscoverToolsWithAuthenticated()` - keep only ONE access control test

### Rationale for Removal

1. **Type Verification**: The discovery service is type-hinted to return `array<string, MethodInterface>`. PHP enforces this at runtime. Testing it provides zero value.

2. **Multiple Access Control Tests**: Testing anonymous users, authenticated users, admin users, and users with specific permissions all test the same underlying access control logic. One representative test is sufficient.

3. **Array Key Preservation**: Testing that array keys match method IDs is trivial and provides no meaningful validation.

### Execution Steps

1. Open `tests/src/Kernel/Service/McpToolDiscoveryServiceTest.php`
2. Remove test methods identified above (keep only ~4)
3. Choose ONE access control test to keep (recommend `testDiscoverToolsRespectsPermissions` as most comprehensive)
4. Run tests: `vendor/bin/phpunit tests/src/Kernel/Service/McpToolDiscoveryServiceTest.php`
5. Fix any issues
6. Run full kernel suite: `vendor/bin/phpunit --group jsonrpc_mcp --testsuite=kernel`
7. Verify all tests pass

### Expected Result

The remaining ~4 tests should validate:

- Methods with McpTool attribute are discovered
- Methods without McpTool attribute are excluded
- Access control is enforced (one representative test)
- Multiple plugins can be discovered

This provides meaningful coverage without redundant testing of framework behavior.

</details>
