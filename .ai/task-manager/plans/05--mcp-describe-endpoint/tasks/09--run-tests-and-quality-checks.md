---
id: 9
group: 'quality-assurance'
dependencies: [6, 7, 8]
status: 'pending'
created: '2025-10-02'
skills:
  - drupal-backend
  - phpunit
---

# Run Test Suite and Code Quality Checks

## Objective

Execute the complete test suite, static analysis, and coding standards checks to verify the implementation meets all quality criteria before considering the feature complete.

## Skills Required

- **drupal-backend**: Understanding of Drupal development tooling, cache management, and quality standards
- **phpunit**: Knowledge of test execution and debugging test failures

## Acceptance Criteria

- [ ] Drupal cache rebuilt after all code changes
- [ ] All functional tests pass (`vendor/bin/phpunit --group jsonrpc_mcp`)
- [ ] PHPStan static analysis passes with no new errors
- [ ] PHPCS coding standards pass for all modified files
- [ ] Manual curl testing confirms all three endpoints work correctly
- [ ] Manual testing verifies permission system functions as expected
- [ ] All 15+ test cases pass (6 describe, 7 invoke, 2 list permission)

Use your internal Todo tool to track these and keep on track.

## Technical Requirements

**Commands to Execute**:

1. `vendor/bin/drush cache:rebuild` - Clear Drupal caches
2. `vendor/bin/phpunit --group jsonrpc_mcp` - Run test suite
3. `vendor/bin/phpstan analyze` - Static analysis
4. `vendor/bin/phpcs --standard=Drupal,DrupalPractice src/ tests/` - Coding standards
5. Manual curl tests for all three endpoints

**Success Criteria**:

- Exit code 0 for all automated checks
- All manual tests return expected responses
- No regressions in existing functionality

## Input Dependencies

- Tasks 6, 7, 8: All test implementations complete
- Tasks 1-5: All feature implementations complete
- Working Drupal environment with jsonrpc_mcp module enabled

## Output Artifacts

- Test execution report (pass/fail for all tests)
- PHPStan analysis results
- PHPCS compliance report
- Manual testing verification notes

## Implementation Notes

<details>
<summary>Detailed Implementation Steps</summary>

1. **Clear caches first**:

   ```bash
   cd /var/www/html
   vendor/bin/drush cache:rebuild
   ```

   This ensures:
   - New routes are discoverable
   - New permission is registered
   - Controller changes are loaded
   - Service container is rebuilt

2. **Run test suite**:

   ```bash
   vendor/bin/phpunit --group jsonrpc_mcp
   ```

   Expected output:
   - All tests pass with OK status
   - Minimum 15 tests executed (existing + new)
   - Tests for: list, describe, invoke endpoints
   - Permission tests for all endpoints

   If tests fail:
   - Read error messages carefully
   - Check for database state issues (test isolation)
   - Verify test module (jsonrpc_mcp_examples) is enabled
   - Debug individual tests: `vendor/bin/phpunit tests/src/Functional/Controller/McpToolsControllerTest.php`

3. **Run PHPStan static analysis**:

   ```bash
   vendor/bin/phpstan analyze
   ```

   Expected:
   - No new errors introduced
   - Existing errors (if any) unchanged
   - Type hints are correct

   Common issues:
   - Missing use statements
   - Type mismatches in method signatures
   - Incorrect return types
   - Undefined variables

4. **Run PHPCS coding standards**:

   ```bash
   vendor/bin/phpcs --standard=Drupal,DrupalPractice \
     src/Controller/McpToolsController.php \
     tests/src/Functional/Controller/McpToolsControllerTest.php \
     jsonrpc_mcp.permissions.yml \
     jsonrpc_mcp.routing.yml
   ```

   Expected:
   - No violations
   - Proper indentation (2 spaces)
   - Proper line length (<80 chars recommended, <120 max)
   - No trailing spaces

   Auto-fix if possible:

   ```bash
   vendor/bin/phpcbf --standard=Drupal,DrupalPractice src/ tests/
   ```

5. **Manual testing: List endpoint**:

   ```bash
   # Test without permission (should fail with 403)
   curl -u unprivileged_user:password \
     "https://drupal-contrib.ddev.site/mcp/tools/list" | jq

   # Test with permission (should succeed)
   curl -u admin:admin \
     "https://drupal-contrib.ddev.site/mcp/tools/list" | jq
   ```

   Verify:
   - 403 status for users without permission
   - 200 status for users with permission
   - Response format: `{"tools": [...], "nextCursor": null}`

6. **Manual testing: Describe endpoint**:

   ```bash
   # Test with valid tool
   curl -u admin:admin \
     "https://drupal-contrib.ddev.site/mcp/tools/describe?name=jsonrpc_mcp_examples.list_content_types" | jq

   # Test with invalid tool
   curl -u admin:admin \
     "https://drupal-contrib.ddev.site/mcp/tools/describe?name=nonexistent.tool" | jq

   # Test without permission
   curl -u unprivileged_user:password \
     "https://drupal-contrib.ddev.site/mcp/tools/describe?name=jsonrpc_mcp_examples.list_content_types" | jq
   ```

   Verify:
   - Valid tool: 200 status, `{"tool": {...}}` format
   - Invalid tool: 404 status, `{"error": {...}}` format
   - No permission: 403 status

7. **Manual testing: Invoke endpoint**:

   ```bash
   # Test successful invocation
   curl -X POST \
     -H "Content-Type: application/json" \
     -u admin:admin \
     -d '{"name":"jsonrpc_mcp_examples.list_content_types","arguments":{}}' \
     https://drupal-contrib.ddev.site/mcp/tools/invoke | jq

   # Test with invalid tool
   curl -X POST \
     -H "Content-Type: application/json" \
     -u admin:admin \
     -d '{"name":"nonexistent.tool","arguments":{}}' \
     https://drupal-contrib.ddev.site/mcp/tools/invoke | jq

   # Test with malformed JSON
   curl -X POST \
     -H "Content-Type: application/json" \
     -u admin:admin \
     -d '{invalid json}' \
     https://drupal-contrib.ddev.site/mcp/tools/invoke | jq

   # Test without required JSON-RPC permission
   curl -X POST \
     -H "Content-Type: application/json" \
     -u unprivileged_user:password \
     -d '{"name":"jsonrpc_mcp_examples.list_content_types","arguments":{}}' \
     https://drupal-contrib.ddev.site/mcp/tools/invoke | jq
   ```

   Verify:
   - Valid request: 200 status, `{"result": {...}}` format
   - Invalid tool: 404 status, `{"error": {...}}` format
   - Malformed JSON: 400 status, `{"error": {...}}` format
   - No permission: Error response (may be 200 with error object or 403)

8. **Permission system verification**:
   - Navigate to `/admin/people/permissions` in Drupal UI
   - Verify "Access MCP tool discovery" permission exists
   - Verify permission appears under "jsonrpc_mcp module" section
   - Grant permission to a test role
   - Verify users with that role can access list/describe endpoints

9. **Regression testing**:
   - Verify existing list endpoint still works (with permission)
   - Verify existing McpToolNormalizer output unchanged
   - Verify existing McpToolDiscoveryService behavior unchanged
   - No breaking changes to existing MCP tool annotations

10. **Performance sanity check**:
    - List endpoint: Should respond in <1 second for reasonable tool counts
    - Describe endpoint: Should respond in <500ms (no pagination needed)
    - Invoke endpoint: Performance depends on tool execution (variable)

11. **Documentation of test results**:
    Create a simple test summary:

    ```
    Test Results Summary:
    ✓ PHPUnit: X tests, Y assertions, all passed
    ✓ PHPStan: No errors
    ✓ PHPCS: No violations
    ✓ Manual list tests: Pass
    ✓ Manual describe tests: Pass
    ✓ Manual invoke tests: Pass
    ✓ Permission system: Working correctly
    ```

12. **Troubleshooting common issues**:

    **Tests fail with "route not found"**:
    - Run `vendor/bin/drush cache:rebuild`
    - Verify routing.yml syntax is correct

    **Tests fail with "permission not found"**:
    - Run `vendor/bin/drush cache:rebuild`
    - Verify permissions.yml syntax is correct

    **Tests fail with "service not found"**:
    - Verify controller constructor matches create() method
    - Check service IDs are correct

    **PHPCS violations**:
    - Run `vendor/bin/phpcbf` to auto-fix
    - Manually fix any remaining issues

    **PHPStan errors**:
    - Add missing use statements
    - Fix type hints
    - Add PHPDoc comments if needed

13. **Completion criteria**: - All automated tests pass ✓ - All manual tests pass ✓ - All quality checks pass ✓ - No regressions identified ✓ - Feature is production-ready ✓
</details>
