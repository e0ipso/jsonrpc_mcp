---
id: 2
group: 'mcp-tool-attribute'
dependencies: [1]
status: 'pending'
created: 2025-10-01
skills:
  - PHPUnit unit testing
  - PHP Reflection API
---

# Create Comprehensive Unit Test Suite for McpTool Attribute

## Objective

Implement comprehensive PHPUnit unit tests for the McpTool attribute class, achieving 100% code coverage and validating all behavior requirements.

## Skills Required

- PHPUnit unit testing (test class structure, assertions, expectations)
- PHP Reflection API (reading attributes from classes via reflection)

## Acceptance Criteria

- [ ] File `tests/src/Unit/Attribute/McpToolTest.php` exists with correct namespace
- [ ] Test class extends `PHPUnit\Framework\TestCase`
- [ ] Uses `@group jsonrpc_mcp` annotation for filtering
- [ ] Uses `@coversDefaultClass \Drupal\jsonrpc_mcp\Attribute\McpTool` annotation
- [ ] Tests default construction (null parameters)
- [ ] Tests construction with title parameter
- [ ] Tests construction with annotations parameter
- [ ] Tests readonly property immutability
- [ ] Tests rejection of indexed arrays for annotations
- [ ] Tests acceptance of associative arrays for annotations
- [ ] Tests acceptance of nested array structures in annotations
- [ ] Tests reflection can read attribute from a class
- [ ] All tests pass with 100% code coverage of McpTool class

## Technical Requirements

**Test File Location:** `tests/src/Unit/Attribute/McpToolTest.php`

**Test Coverage Requirements:**

1. **Basic Instantiation Tests:**
   - `testDefaultConstruction()`: Verify null defaults
   - `testWithTitle()`: Verify title storage
   - `testWithAnnotations()`: Verify annotations storage
   - `testWithBothParameters()`: Verify both parameters work together

2. **Validation Tests:**
   - `testRejectsListAnnotations()`: Expect InvalidArgumentException for indexed arrays
   - `testAcceptsNestedAnnotations()`: Verify complex nested structures work

3. **Integration Tests:**
   - `testAttributeOnClass()`: Use reflection to read attribute from a test class
   - Verify attribute data is accessible via reflection API

**Test Pattern Example:**

```php
namespace Drupal\Tests\jsonrpc_mcp\Unit\Attribute;

use Drupal\jsonrpc_mcp\Attribute\McpTool;
use PHPUnit\Framework\TestCase;

/**
 * @group jsonrpc_mcp
 * @coversDefaultClass \Drupal\jsonrpc_mcp\Attribute\McpTool
 */
class McpToolTest extends TestCase {

  /**
   * @covers ::__construct
   */
  public function testDefaultConstruction(): void {
    $attribute = new McpTool();
    $this->assertNull($attribute->title);
    $this->assertNull($attribute->annotations);
  }

  // Additional test methods...
}
```

## Input Dependencies

Requires task 1 completed: `src/Attribute/McpTool.php` must exist and be functional.

## Output Artifacts

- `tests/src/Unit/Attribute/McpToolTest.php` - Complete unit test suite
- Verification that all tests pass: `vendor/bin/phpunit --group jsonrpc_mcp tests/src/Unit/Attribute/`

## Implementation Notes

- This is a pure unit test - no Drupal bootstrap required
- Use `$this->expectException()` for validation failure tests
- Test with various data types for comprehensive coverage
- Include edge cases: empty strings, large arrays, special characters
- Document each test method with clear `@covers` annotations
