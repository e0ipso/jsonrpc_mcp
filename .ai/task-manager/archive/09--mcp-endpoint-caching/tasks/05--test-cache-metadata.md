---
id: 5
group: 'testing'
dependencies: [2]
status: 'completed'
created: '2025-10-02'
completed: '2025-10-02'
skills:
  - drupal-backend
  - phpunit
---

# Write kernel tests for cache metadata attachment

## Objective

Create kernel tests that verify cache metadata (tags, contexts, max-age) is correctly attached to discovery endpoint responses without requiring a full Drupal installation or browser.

## Skills Required

- **drupal-backend**: Understanding of Drupal cache API and kernel testing
- **phpunit**: Writing and organizing kernel test cases

## Acceptance Criteria

- [x] Kernel test class `McpToolsCacheMetadataTest.php` is created
- [x] Test verifies `list()` response has correct cache tags
- [x] Test verifies `list()` response has correct cache contexts
- [x] Test verifies `list()` response has permanent max-age
- [x] Test verifies `describe()` response has correct cache metadata
- [x] Test verifies `invoke()` response has max-age of 0
- [x] All tests pass and follow Drupal coding standards

## Technical Requirements

**Test file location**: `tests/src/Kernel/Controller/McpToolsCacheMetadataTest.php`

**Test framework**: Drupal `KernelTestBase`

**Test coverage**:

1. Cache tags: `['jsonrpc_mcp:discovery', 'user.permissions']`
2. Cache contexts: `['user', 'url.query_args:cursor']` for list, `['user', 'url.query_args:name']` for describe
3. Max-age: `Cache::PERMANENT` for discovery, `0` for invoke

## Input Dependencies

- Task 2: Cache metadata must be implemented in controller

## Output Artifacts

- Kernel test class validating cache metadata
- Test coverage for caching behavior

## Implementation Notes

<details>
<summary>Detailed implementation steps - Meaningful Test Strategy Guidelines</summary>

**IMPORTANT**: Your critical mantra for test generation is: "write a few tests, mostly integration".

Focus on testing the **custom business logic** of cache metadata attachment, not framework functionality.

### Test Class Structure

Create `tests/src/Kernel/Controller/McpToolsCacheMetadataTest.php`:

```php
<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonrpc_mcp\Kernel\Controller;

use Drupal\Core\Cache\Cache;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests cache metadata attachment to MCP controller responses.
 *
 * @group jsonrpc_mcp
 */
class McpToolsCacheMetadataTest extends KernelTestBase {

  protected static $modules = ['jsonrpc_mcp', 'jsonrpc', 'serialization'];

  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['jsonrpc_mcp']);
  }

  /**
   * Tests cache metadata on list endpoint response.
   */
  public function testListEndpointCacheMetadata(): void {
    $controller = $this->container->get('jsonrpc_mcp.tools_controller');
    $request = Request::create('/mcp/tools/list', 'GET');

    $response = $controller->list($request);
    $cache_metadata = $response->getCacheableMetadata();

    // Assert cache tags.
    $this->assertContains('jsonrpc_mcp:discovery', $cache_metadata->getCacheTags());
    $this->assertContains('user.permissions', $cache_metadata->getCacheTags());

    // Assert cache contexts.
    $this->assertContains('user', $cache_metadata->getCacheContexts());
    $this->assertContains('url.query_args:cursor', $cache_metadata->getCacheContexts());

    // Assert permanent max-age.
    $this->assertEquals(Cache::PERMANENT, $cache_metadata->getCacheMaxAge());
  }

  /**
   * Tests cache metadata on describe endpoint response.
   */
  public function testDescribeEndpointCacheMetadata(): void {
    $controller = $this->container->get('jsonrpc_mcp.tools_controller');
    $request = Request::create('/mcp/tools/describe', 'GET', ['name' => 'test.method']);

    $response = $controller->describe($request);
    $cache_metadata = $response->getCacheableMetadata();

    // Assert cache tags.
    $this->assertContains('jsonrpc_mcp:discovery', $cache_metadata->getCacheTags());
    $this->assertContains('user.permissions', $cache_metadata->getCacheTags());

    // Assert cache contexts include query arg.
    $this->assertContains('url.query_args:name', $cache_metadata->getCacheContexts());

    // Assert permanent max-age.
    $this->assertEquals(Cache::PERMANENT, $cache_metadata->getCacheMaxAge());
  }

  /**
   * Tests invoke endpoint is not cached.
   */
  public function testInvokeEndpointNotCached(): void {
    $controller = $this->container->get('jsonrpc_mcp.tools_controller');

    $request_body = json_encode([
      'name' => 'test.method',
      'arguments' => [],
    ]);
    $request = Request::create('/mcp/tools/invoke', 'POST', [], [], [], [], $request_body);

    // Note: This test may fail if no valid tool exists; adjust based on test setup
    $response = $controller->invoke($request);
    $cache_metadata = $response->getCacheableMetadata();

    // Assert max-age is 0 (no caching).
    $this->assertEquals(0, $cache_metadata->getCacheMaxAge());
  }

}
```

### Key Testing Points

1. **Test cache tag presence**: Verify both custom and permission tags exist
2. **Test cache contexts**: Ensure user and query parameter contexts are set
3. **Test max-age values**: Confirm permanent caching for discovery, none for invoke
4. **Focus on custom logic**: Test YOUR cache metadata attachment, not Drupal's cache system

### What NOT to Test

- ❌ How Drupal's cache system stores/retrieves cached items (framework functionality)
- ❌ How `CacheableMetadata::merge()` works (tested by Drupal core)
- ❌ Page cache storage mechanisms (integration test concern)
- ❌ Cache tag invalidation mechanics (functional test concern)

### Running the Tests

```bash
vendor/bin/phpunit --group jsonrpc_mcp tests/src/Kernel/Controller/McpToolsCacheMetadataTest.php
```

**Important notes**:

- Keep tests focused on verifying cache metadata values
- Avoid testing Drupal core functionality
- Use mocking if JSON-RPC handler isn't available in kernel tests
- Consider creating a test MCP tool plugin for more reliable testing
</details>
