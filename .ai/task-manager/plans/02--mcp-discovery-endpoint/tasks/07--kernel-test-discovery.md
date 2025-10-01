---
id: 7
group: 'mcp-discovery-endpoint'
dependencies: [1, 9]
status: 'pending'
created: '2025-10-01'
'skills: ['drupal-kernel-testing', 'drupal-plugins']
---

# Create Kernel Tests for McpToolDiscoveryService

## Objective

Implement Drupal kernel tests for McpToolDiscoveryService using real plugin classes from the test module to verify attribute detection and access control.

## Skills Required

- Drupal kernel testing (KernelTestBase, module installation, service access)
- Drupal plugin system (understanding plugin discovery and loading)

## Acceptance Criteria

- [ ] File `tests/src/Kernel/Service/McpToolDiscoveryServiceTest.php` exists
- [ ] Test class extends `KernelTestBase`
- [ ] Uses `@group jsonrpc_mcp` annotation
- [ ] Declares `$modules` including `jsonrpc`, `jsonrpc_mcp`, `jsonrpc_mcp_test`
- [ ] Tests discovery of methods with McpTool attribute
- [ ] Tests filtering (methods without McpTool are excluded)
- [ ] Tests access control (methods user cannot access are excluded)
- [ ] Tests with different user permission scenarios
- [ ] All tests pass: `vendor/bin/phpunit --group jsonrpc_mcp tests/src/Kernel/Service/`

## Technical Requirements

**File Location:** `tests/src/Kernel/Service/McpToolDiscoveryServiceTest.php`

**Test Coverage Requirements:**

1. **Discovery Tests:**
   - `testDiscoverToolsFindsMarkedMethods()`: Verify methods with McpTool are found
   - `testDiscoverToolsExcludesUnmarkedMethods()`: Verify methods without McpTool are excluded
   - `testDiscoverToolsReturnsMethodInterface()`: Verify return type

2. **Access Control Tests:**
   - `testDiscoverToolsRespectsPermissions()`: Test with user lacking permissions
   - `testDiscoverToolsWithAuthenticated()`: Test with authenticated user
   - `testDiscoverToolsWithAnonymous()`: Test with anonymous user

3. **Integration Tests:**
   - `testDiscoveryWithMultiplePlugins()`: Multiple test plugins
   - `testEmptyResultWhenNoMarkedMethods()`: No MCP tools available

**Test Pattern:**

```php
namespace Drupal\Tests\jsonrpc_mcp\Kernel\Service;

use Drupal\KernelTests\KernelTestBase;

/**
 * @group jsonrpc_mcp
 * @coversDefaultClass \Drupal\jsonrpc_mcp\Service\McpToolDiscoveryService
 */
class McpToolDiscoveryServiceTest extends KernelTestBase {

  protected static $modules = [
    'system',
    'user',
    'jsonrpc',
    'jsonrpc_mcp',
    'jsonrpc_mcp_test',
  ];

  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installConfig(['system', 'user']);
  }

  /**
   * @covers ::discoverTools
   */
  public function testDiscoverToolsFindsMarkedMethods(): void {
    $discovery = $this->container->get('jsonrpc_mcp.tool_discovery');
    $tools = $discovery->discoverTools();

    // Should find test method from jsonrpc_mcp_test module
    $this->assertArrayHasKey('test.example', $tools);
    $this->assertInstanceOf(MethodInterface::class, $tools['test.example']);
  }

  // Additional test methods...
}
```

## Input Dependencies

- Task 001 completed (McpToolDiscoveryService exists)
- Task 009 completed (Test plugin in jsonrpc_mcp_test module exists)

## Output Artifacts

- `tests/src/Kernel/Service/McpToolDiscoveryServiceTest.php` - Complete kernel test suite
- Verification that tests pass with real plugin system

## Implementation Notes

- Kernel tests bootstrap Drupal's container and plugin system
- Must install entity schemas and configs in setUp()
- Use test module's plugins for realistic testing
- Test with different user contexts using setCurrentUser()
- These tests verify integration with jsonrpc module's plugin discovery
