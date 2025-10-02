---
id: 6
group: 'testing'
dependencies: [4, 5]
status: 'pending'
created: '2025-10-02'
skills:
  - drupal-backend
  - phpunit
---

# Write functional tests for cache invalidation behavior

## Objective

Create functional tests that verify cache invalidation triggers work correctly when modules are installed/uninstalled or when the invalidation service method is called.

## Skills Required

- **drupal-backend**: Understanding of Drupal module lifecycle and cache invalidation
- **phpunit**: Writing functional tests with full Drupal bootstrap

## Acceptance Criteria

- [ ] Functional test class `McpToolsCacheInvalidationTest.php` is created
- [ ] Test verifies cache invalidation on module install
- [ ] Test verifies cache invalidation on module uninstall
- [ ] Test verifies manual cache invalidation via service method
- [ ] Test verifies cache is NOT invalidated on unrelated operations
- [ ] All tests pass and follow Drupal coding standards

## Technical Requirements

**Test file location**: `tests/src/Functional/McpToolsCacheInvalidationTest.php`

**Test framework**: Drupal `BrowserTestBase`

**Test coverage**:

1. Module install triggers `jsonrpc_mcp:discovery` tag invalidation
2. Module uninstall triggers cache invalidation
3. Service method `invalidateDiscoveryCache()` clears cache
4. Unrelated operations don't invalidate cache

## Input Dependencies

- Task 4: Cache invalidation logic must be implemented
- Task 5: Kernel tests provide foundation for understanding cache metadata

## Output Artifacts

- Functional test class validating invalidation behavior
- Integration test coverage for cache lifecycle

## Implementation Notes

<details>
<summary>Detailed implementation steps - Meaningful Test Strategy Guidelines</summary>

**IMPORTANT**: Your critical mantra for test generation is: "write a few tests, mostly integration".

Focus on testing the **integration** of cache invalidation with Drupal's module system, not testing the framework.

### Test Class Structure

Create `tests/src/Functional/McpToolsCacheInvalidationTest.php`:

```php
<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonrpc_mcp\Functional;

use Drupal\Core\Cache\Cache;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests cache invalidation for MCP tool discovery.
 *
 * @group jsonrpc_mcp
 */
class McpToolsCacheInvalidationTest extends BrowserTestBase {

  protected static $modules = ['jsonrpc_mcp', 'jsonrpc'];
  protected $defaultTheme = 'stark';

  /**
   * Tests cache invalidation when modules are installed.
   */
  public function testCacheInvalidationOnModuleInstall(): void {
    // Create a user with permission to access discovery endpoint.
    $user = $this->drupalCreateUser(['access mcp tool discovery']);
    $this->drupalLogin($user);

    // Make initial request to populate cache.
    $this->drupalGet('/mcp/tools/list');
    $this->assertSession()->statusCodeEquals(200);

    // Verify response is cached (check for cache hit on second request).
    // Note: Actual cache hit verification may require additional setup.

    // Install a module (use a test module or lightweight core module).
    \Drupal::service('module_installer')->install(['help']);

    // Verify cache was invalidated by checking if discovery picks up changes.
    $this->drupalGet('/mcp/tools/list');
    $this->assertSession()->statusCodeEquals(200);

    // Cache should be regenerated, not served from stale cache.
  }

  /**
   * Tests cache invalidation when modules are uninstalled.
   */
  public function testCacheInvalidationOnModuleUninstall(): void {
    $user = $this->drupalCreateUser(['access mcp tool discovery']);
    $this->drupalLogin($user);

    // Install a test module first.
    \Drupal::service('module_installer')->install(['help']);

    // Populate cache.
    $this->drupalGet('/mcp/tools/list');
    $this->assertSession()->statusCodeEquals(200);

    // Uninstall the module.
    \Drupal::service('module_installer')->uninstall(['help']);

    // Verify cache was invalidated.
    $this->drupalGet('/mcp/tools/list');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests manual cache invalidation via service method.
   */
  public function testManualCacheInvalidation(): void {
    $user = $this->drupalCreateUser(['access mcp tool discovery']);
    $this->drupalLogin($user);

    // Populate cache.
    $this->drupalGet('/mcp/tools/list');
    $this->assertSession()->statusCodeEquals(200);

    // Manually invalidate cache.
    \Drupal::service('jsonrpc_mcp.tool_discovery')->invalidateDiscoveryCache();

    // Verify cache was cleared.
    $cache_tags = ['jsonrpc_mcp:discovery'];
    $cache = \Drupal::cache('page');

    // Check that cached items with this tag are invalidated.
    // Note: Actual verification may require inspecting cache backend.

    $this->drupalGet('/mcp/tools/list');
    $this->assertSession()->statusCodeEquals(200);
  }

}
```

### Key Testing Points

1. **Test module lifecycle integration**: Verify hooks trigger cache invalidation
2. **Test service method**: Confirm manual invalidation works
3. **Test cache behavior**: Verify cached responses are refreshed after invalidation
4. **Focus on integration**: Test how YOUR module integrates with Drupal's module system

### What NOT to Test

- ❌ How `Cache::invalidateTags()` internally works (framework functionality)
- ❌ How the module installer service works (Drupal core functionality)
- ❌ How page cache stores/retrieves items (cache backend concern)
- ❌ Every possible module install scenario (test critical paths only)

### Simplification Strategy

Instead of comprehensive tests for every edge case:

- Test ONE module install scenario (proves hook works)
- Test ONE uninstall scenario (proves hook works)
- Test ONE manual invalidation (proves service method works)

This covers the critical integration points without over-testing framework functionality.

### Running the Tests

```bash
vendor/bin/phpunit --group jsonrpc_mcp tests/src/Functional/McpToolsCacheInvalidationTest.php
```

**Important notes**:

- Focus on integration between module hooks and cache system
- Don't test Drupal core's module installer or cache invalidation mechanics
- Keep test scenarios minimal but meaningful
- Use lightweight test modules (like 'help') for install/uninstall tests
</details>
