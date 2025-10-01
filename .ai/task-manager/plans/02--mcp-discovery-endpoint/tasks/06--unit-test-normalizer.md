---
id: 6
group: 'mcp-discovery-endpoint'
dependencies: [2]
status: 'pending'
created: '2025-10-01'
'skills: ['phpunit', 'test-doubles']
---

# Create Unit Tests for McpToolNormalizer

## Objective

Implement comprehensive PHPUnit unit tests for the McpToolNormalizer class, testing schema transformation logic with mocked method objects.

## Skills Required

- PHPUnit unit testing (test class structure, assertions, data providers)
- Test doubles (mocking MethodInterface and attribute data)

## Acceptance Criteria

- [ ] File `tests/src/Unit/Normalizer/McpToolNormalizerTest.php` exists
- [ ] Test class extends `PHPUnit\Framework\TestCase`
- [ ] Uses `@group jsonrpc_mcp` annotation
- [ ] Uses `@coversDefaultClass \Drupal\jsonrpc_mcp\Normalizer\McpToolNormalizer`
- [ ] Tests transformation of all mapping rules (id→name, usage→description, etc.)
- [ ] Tests inputSchema generation with empty parameters
- [ ] Tests inputSchema generation with multiple parameters
- [ ] Tests required parameter handling
- [ ] Tests outputSchema addition when method implements it
- [ ] Tests McpTool attribute extraction (title, annotations)
- [ ] Tests TranslatableMarkup conversion to string
- [ ] All tests pass: `vendor/bin/phpunit --group jsonrpc_mcp tests/src/Unit/Normalizer/`

## Technical Requirements

**File Location:** `tests/src/Unit/Normalizer/McpToolNormalizerTest.php`

**Test Coverage Requirements:**

1. **Basic Transformation Tests:**
   - `testNormalizeWithMinimalMethod()`: Method with only required fields
   - `testNormalizeWithTitle()`: Method with McpTool title
   - `testNormalizeWithAnnotations()`: Method with McpTool annotations
   - `testNormalizeWithOutputSchema()`: Method implementing outputSchema()

2. **InputSchema Tests:**
   - `testBuildInputSchemaEmpty()`: No parameters
   - `testBuildInputSchemaWithRequiredParams()`: Required parameters
   - `testBuildInputSchemaWithOptionalParams()`: Optional parameters
   - `testBuildInputSchemaWithDescriptions()`: Parameters with descriptions

3. **Edge Case Tests:**
   - `testTranslatableMarkupConversion()`: Verify string casting
   - `testNestedParameterSchemas()`: Complex schema structures
   - `testMethodWithoutMcpToolAttribute()`: Fallback behavior

**Mock Setup Pattern:**

```php
namespace Drupal\Tests\jsonrpc_mcp\Unit\Normalizer;

use Drupal\jsonrpc_mcp\Normalizer\McpToolNormalizer;
use Drupal\jsonrpc\MethodInterface;
use PHPUnit\Framework\TestCase;

/**
 * @group jsonrpc_mcp
 * @coversDefaultClass \Drupal\jsonrpc_mcp\Normalizer\McpToolNormalizer
 */
class McpToolNormalizerTest extends TestCase {

  protected McpToolNormalizer $normalizer;

  protected function setUp(): void {
    parent::setUp();
    $this->normalizer = new McpToolNormalizer();
  }

  /**
   * @covers ::normalize
   */
  public function testNormalizeWithMinimalMethod(): void {
    // Create mock MethodInterface
    $method = $this->createMock(MethodInterface::class);
    $method->method('getPluginDefinition')
      ->willReturn([
        'id' => 'test.method',
        'usage' => 'Test description',
        'params' => [],
        'class' => TestMethodClass::class,
      ]);

    $result = $this->normalizer->normalize($method);

    $this->assertSame('test.method', $result['name']);
    $this->assertSame('Test description', $result['description']);
    $this->assertArrayHasKey('inputSchema', $result);
  }

  // Additional test methods...
}
```

## Input Dependencies

- Task 002 completed (McpToolNormalizer class exists)

## Output Artifacts

- `tests/src/Unit/Normalizer/McpToolNormalizerTest.php` - Complete unit test suite
- Verification that tests pass

## Implementation Notes

- Use PHPUnit's mock objects for MethodInterface
- Create test stub classes for reflection tests
- Test with various data types for comprehensive coverage
- Include edge cases: empty arrays, TranslatableMarkup objects, nested schemas
- No Drupal bootstrap required - pure unit tests
