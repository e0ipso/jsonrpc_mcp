---
id: 8
group: 'testing'
dependencies: [2]
status: 'pending'
created: '2025-10-02'
skills:
  - phpunit
  - drupal-backend
---

# Update List Endpoint Tests for Permission Change

## Objective

Update existing functional tests for the `/mcp/tools/list` endpoint to account for the new "access mcp tool discovery" permission requirement, ensuring the breaking change is properly tested.

## Skills Required

- **phpunit**: Experience modifying existing Drupal functional tests
- **drupal-backend**: Understanding of permission-based access control changes

## Acceptance Criteria

- [ ] Existing list endpoint tests updated to create users with "access mcp tool discovery" permission
- [ ] New test added: `testListEndpointPermissionDenied()` - verifies 403 without permission
- [ ] New test added: `testListEndpointWithPermission()` - verifies 200 with permission (if not already covered)
- [ ] All existing tests still pass after permission change
- [ ] Tests verify breaking change behavior (403 for unauthorized users)
- [ ] Test execution confirms permission requirement works correctly

Use your internal Todo tool to track these and keep on track.

## Technical Requirements

**Test File**: `tests/src/Functional/Controller/McpToolsControllerTest.php`

**Changes Required**:

1. Update user creation in existing list tests to include permission
2. Add new test for permission denied scenario
3. Add new test for permission granted scenario (regression test)

**Permission**: `access mcp tool discovery`

## Input Dependencies

- Task 2: Updated routing configuration with permission requirement
- Existing `McpToolsControllerTest.php` with list endpoint tests

## Output Artifacts

- Updated `tests/src/Functional/Controller/McpToolsControllerTest.php` with permission-aware tests

## Implementation Notes

<details>
<summary>Detailed Implementation Steps</summary>

**IMPORTANT**: Copy this guideline into your implementation:

## Meaningful Test Strategy Guidelines

Your critical mantra: "write a few tests, mostly integration".

**Focus**: Test the breaking change - permission requirement for list endpoint. This is custom business logic that needs verification.

---

1. **Review existing list endpoint tests**:
   - Location: `/var/www/html/web/modules/contrib/jsonrpc_mcp/tests/src/Functional/Controller/McpToolsControllerTest.php`
   - Find all tests that call `/mcp/tools/list`
   - Identify user creation patterns

2. **Update existing tests to grant permission**:

   ```php
   // OLD (if exists):
   $user = $this->drupalCreateUser([]);

   // NEW:
   $user = $this->drupalCreateUser(['access mcp tool discovery']);
   ```

   Update ALL existing list endpoint tests to include the permission in user creation.

3. **Add test for permission denied**:

   ```php
   /**
    * Tests list endpoint returns 403 without permission.
    *
    * This is a regression test for the breaking change that added
    * permission requirement to the list endpoint.
    */
   public function testListEndpointPermissionDenied() {
     // Create user WITHOUT the new permission.
     $user = $this->drupalCreateUser([]);
     $this->drupalLogin($user);

     // Attempt to access list endpoint.
     $this->drupalGet('/mcp/tools/list');

     // Should return 403 Forbidden.
     $this->assertSession()->statusCodeEquals(403);
   }
   ```

4. **Add test for permission granted (if not already covered)**:

   ```php
   /**
    * Tests list endpoint returns 200 with proper permission.
    *
    * Verifies the "access mcp tool discovery" permission grants access.
    */
   public function testListEndpointWithPermission() {
     // Create user WITH the new permission.
     $user = $this->drupalCreateUser(['access mcp tool discovery']);
     $this->drupalLogin($user);

     // Access list endpoint.
     $this->drupalGet('/mcp/tools/list');

     // Should return 200 OK.
     $this->assertSession()->statusCodeEquals(200);

     // Should return valid JSON response.
     $response = json_decode($this->getSession()->getPage()->getContent(), TRUE);
     $this->assertArrayHasKey('tools', $response);
     $this->assertIsArray($response['tools']);
   }
   ```

5. **Identify all tests to update**:
   - Search for tests containing `/mcp/tools/list`
   - Search for `drupalCreateUser()` calls before list endpoint access
   - Common test names: `testList*`, `testToolsList*`, `testDiscovery*`

6. **Pattern for updates**:

   ```php
   // Before permission change:
   public function testListEndpoint() {
     $user = $this->drupalCreateUser([]);  // ← UPDATE THIS
     $this->drupalLogin($user);
     $this->drupalGet('/mcp/tools/list');
     // assertions...
   }

   // After permission change:
   public function testListEndpoint() {
     $user = $this->drupalCreateUser(['access mcp tool discovery']);  // ← UPDATED
     $this->drupalLogin($user);
     $this->drupalGet('/mcp/tools/list');
     // assertions...
   }
   ```

7. **Verify test isolation**:
   - Each test should create its own user
   - Don't rely on shared user fixtures that might not have the permission
   - Ensure setUp() methods create users with appropriate permissions

8. **Test the breaking change**:
   - The new `testListEndpointPermissionDenied()` test specifically verifies the breaking change
   - This documents the new behavior for developers upgrading the module
   - If this test fails, the permission system isn't working

9. **Run updated tests**:

   ```bash
   # Run all jsonrpc_mcp tests
   vendor/bin/phpunit --group jsonrpc_mcp

   # Run only list endpoint tests
   vendor/bin/phpunit --filter testList tests/src/Functional/Controller/McpToolsControllerTest.php
   ```

10. **Expected test outcomes**:
    - All existing tests should still pass (with permission added)
    - New permission denied test should pass (returns 403)
    - New permission granted test should pass (returns 200)

11. **Documentation of breaking change**:
    - These test changes document the breaking change in code
    - The permission denied test serves as regression protection
    - Module upgraders will see test failures if they don't understand the change

12. **Common pitfalls**:
    - Forgetting to update a test that uses list endpoint
    - Not creating explicit permission denied test
    - Assuming anonymous users can access (they can't - need permission)
    - Not testing the specific permission name (typos in permission string)

13. **Minimal test coverage**: - Don't create extensive permission matrix tests - Focus on: denied (no permission), granted (with permission) - Existing list functionality tests remain unchanged (just add permission) - This is sufficient to verify the breaking change works correctly
</details>
