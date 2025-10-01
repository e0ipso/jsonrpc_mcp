---
id: 6
group: 'testing'
dependencies: [2]
status: 'completed'
created: '2025-10-01'
skills: ['php', 'phpunit']
---

# Create Unit Tests for Markdown Conversion

## Objective

Write focused unit tests for the markdown conversion logic in ArticleToMarkdown, testing HTML stripping and paragraph separation in isolation without Drupal bootstrap.

## Skills Required

- `php`: Regular expressions, string manipulation
- `phpunit`: Unit testing, mocking, test isolation

## Acceptance Criteria

- [ ] `MarkdownConverterTest.php` unit test class created
- [ ] Test covers basic HTML paragraph conversion to double newlines
- [ ] Test covers HTML tag stripping
- [ ] Test covers edge cases: empty body, no paragraphs, nested HTML
- [ ] Test uses mocked NodeInterface (no database dependencies)
- [ ] All tests pass with `vendor/bin/phpunit --testsuite=unit`

## Technical Requirements

- Test class: `tests/src/Unit/MarkdownConverterTest.php`
- Namespace: `Drupal\Tests\jsonrpc_mcp_examples\Unit`
- Extends: `UnitTestCase`
- Uses reflection or public method extraction to test conversion logic
- Group annotation: `@group jsonrpc_mcp_examples`

## Input Dependencies

- Task 2: ArticleToMarkdown implementation (specifically the convertToMarkdown method)

## Output Artifacts

- `modules/jsonrpc_mcp_examples/tests/src/Unit/MarkdownConverterTest.php`

<details>
<summary>Implementation Notes</summary>

### Meaningful Test Strategy Guidelines

Your critical mantra for test generation is: "write a few tests, mostly integration".

**Focus on:**

- Custom markdown conversion logic (our code)
- Edge cases in HTML parsing (empty strings, nested tags, malformed HTML)
- Paragraph separation logic (double newlines)

**Avoid testing:**

- PHP's built-in strip_tags() function (already tested by PHP)
- PHP's preg_replace() function (framework functionality)

### Test Class Structure

```php
<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonrpc_mcp_examples\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\jsonrpc_mcp_examples\Plugin\jsonrpc\Method\ArticleToMarkdown;
use Drupal\node\NodeInterface;

/**
 * Tests markdown conversion logic.
 *
 * @group jsonrpc_mcp_examples
 */
class MarkdownConverterTest extends UnitTestCase {

  /**
   * Tests basic paragraph conversion.
   */
  public function testBasicParagraphConversion(): void {
    $node = $this->createMockNode('Test Title', '<p>First paragraph.</p><p>Second paragraph.</p>');
    $method = $this->createMethod();
    $result = $this->invokeConversion($method, $node);

    $expected = "# Test Title\n\nFirst paragraph.\n\nSecond paragraph.";
    $this->assertEquals($expected, $result);
  }

  /**
   * Tests HTML tag stripping.
   */
  public function testHtmlTagStripping(): void {
    $node = $this->createMockNode('Test', '<p>Text with <strong>bold</strong> and <em>italic</em>.</p>');
    $method = $this->createMethod();
    $result = $this->invokeConversion($method, $node);

    $expected = "# Test\n\nText with bold and italic.";
    $this->assertEquals($expected, $result);
  }

  /**
   * Tests empty body field.
   */
  public function testEmptyBody(): void {
    $node = $this->createMockNode('Empty Article', '');
    $method = $this->createMethod();
    $result = $this->invokeConversion($method, $node);

    $expected = "# Empty Article\n\n";
    $this->assertEquals($expected, $result);
  }

  /**
   * Tests body without paragraph tags.
   */
  public function testBodyWithoutParagraphs(): void {
    $node = $this->createMockNode('Test', 'Plain text without tags');
    $method = $this->createMethod();
    $result = $this->invokeConversion($method, $node);

    $expected = "# Test\n\nPlain text without tags";
    $this->assertEquals($expected, $result);
  }

  /**
   * Tests nested HTML elements.
   */
  public function testNestedHtml(): void {
    $node = $this->createMockNode('Test', '<p><span>Nested <strong>tags</strong> here</span>.</p>');
    $method = $this->createMethod();
    $result = $this->invokeConversion($method, $node);

    $expected = "# Test\n\nNested tags here.";
    $this->assertEquals($expected, $result);
  }

  /**
   * Tests multiple consecutive paragraphs.
   */
  public function testMultipleParagraphs(): void {
    $body = '<p>First.</p><p>Second.</p><p>Third.</p>';
    $node = $this->createMockNode('Test', $body);
    $method = $this->createMethod();
    $result = $this->invokeConversion($method, $node);

    $expected = "# Test\n\nFirst.\n\nSecond.\n\nThird.";
    $this->assertEquals($expected, $result);
  }

  /**
   * Tests paragraph tags with attributes.
   */
  public function testParagraphsWithAttributes(): void {
    $body = '<p class="intro">First paragraph.</p><p style="color:red;">Second paragraph.</p>';
    $node = $this->createMockNode('Test', $body);
    $method = $this->createMethod();
    $result = $this->invokeConversion($method, $node);

    $expected = "# Test\n\nFirst paragraph.\n\nSecond paragraph.";
    $this->assertEquals($expected, $result);
  }

  /**
   * Creates a mock node with title and body.
   */
  protected function createMockNode(string $title, string $body): NodeInterface {
    $bodyField = $this->createMock(\Drupal\Core\Field\FieldItemListInterface::class);
    $bodyField->value = $body;

    $node = $this->createMock(NodeInterface::class);
    $node->method('getTitle')->willReturn($title);
    $node->method('get')->with('body')->willReturn($bodyField);

    return $node;
  }

  /**
   * Creates an instance of ArticleToMarkdown for testing.
   */
  protected function createMethod(): ArticleToMarkdown {
    $entityTypeManager = $this->createMock(\Drupal\Core\Entity\EntityTypeManagerInterface::class);

    return new ArticleToMarkdown(
      [],
      'examples.article.toMarkdown',
      $this->createMock(\Drupal\jsonrpc\MethodInterface::class),
      $entityTypeManager
    );
  }

  /**
   * Invokes the protected convertToMarkdown method using reflection.
   */
  protected function invokeConversion(ArticleToMarkdown $method, NodeInterface $node): string {
    $reflection = new \ReflectionClass($method);
    $convertMethod = $reflection->getMethod('convertToMarkdown');
    $convertMethod->setAccessible(TRUE);

    return $convertMethod->invoke($method, $node);
  }

}
```

### Key Testing Points

1. **Mocking**: Create mock NodeInterface without database dependencies
2. **Reflection**: Use ReflectionClass to access protected convertToMarkdown method
3. **Edge Cases**:
   - Empty body
   - Body without paragraph tags
   - Nested HTML elements
   - Paragraph tags with attributes
4. **Assertions**: Verify exact markdown output including double newlines
5. **Isolation**: No Drupal bootstrap, pure PHP unit tests

### Running Tests

```bash
# Run all unit tests
vendor/bin/phpunit --testsuite=unit

# Run this specific test
vendor/bin/phpunit modules/jsonrpc_mcp_examples/tests/src/Unit/MarkdownConverterTest.php
```

### Alternative Approach: Extract Logic

If reflection feels cumbersome, consider extracting the conversion logic to a standalone service or trait that can be tested directly without reflection. This would be a future refactoring opportunity.

</details>
