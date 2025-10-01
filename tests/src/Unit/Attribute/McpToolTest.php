<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonrpc_mcp\Unit\Attribute;

use Drupal\jsonrpc_mcp\Attribute\McpTool;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the McpTool attribute class.
 *
 * @group jsonrpc_mcp
 * @coversDefaultClass \Drupal\jsonrpc_mcp\Attribute\McpTool
 */
class McpToolTest extends TestCase {

  /**
   * Tests default construction with null parameters.
   *
   * @covers ::__construct
   */
  public function testDefaultConstruction(): void {
    $attribute = new McpTool();
    $this->assertNull($attribute->title);
    $this->assertNull($attribute->annotations);
  }

  /**
   * Tests construction with title parameter.
   *
   * @covers ::__construct
   */
  public function testWithTitle(): void {
    $title = 'Test Tool Title';
    $attribute = new McpTool(title: $title);
    $this->assertSame($title, $attribute->title);
    $this->assertNull($attribute->annotations);
  }

  /**
   * Tests construction with annotations parameter.
   *
   * @covers ::__construct
   */
  public function testWithAnnotations(): void {
    $annotations = ['category' => 'test', 'version' => '1.0'];
    $attribute = new McpTool(annotations: $annotations);
    $this->assertNull($attribute->title);
    $this->assertSame($annotations, $attribute->annotations);
  }

  /**
   * Tests construction with both title and annotations.
   *
   * @covers ::__construct
   */
  public function testWithBothParameters(): void {
    $title = 'Complete Tool';
    $annotations = ['category' => 'advanced'];
    $attribute = new McpTool(title: $title, annotations: $annotations);
    $this->assertSame($title, $attribute->title);
    $this->assertSame($annotations, $attribute->annotations);
  }

  /**
   * Tests that empty string title is accepted.
   *
   * @covers ::__construct
   */
  public function testWithEmptyTitle(): void {
    $attribute = new McpTool(title: '');
    $this->assertSame('', $attribute->title);
  }

  /**
   * Tests that empty array is treated as a list and rejected.
   *
   * PHP's array_is_list() returns true for empty arrays.
   *
   * @covers ::__construct
   */
  public function testRejectsEmptyArrayAnnotations(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('McpTool annotations must be an associative array');
    new McpTool(annotations: []);
  }

  /**
   * Tests rejection of indexed arrays (lists) for annotations.
   *
   * @covers ::__construct
   */
  public function testRejectsListAnnotations(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('McpTool annotations must be an associative array');
    new McpTool(annotations: ['item1', 'item2', 'item3']);
  }

  /**
   * Tests acceptance of arrays with non-sequential numeric keys.
   *
   * Arrays with non-sequential keys are considered associative by
   * array_is_list().
   *
   * @covers ::__construct
   */
  public function testAcceptsNonSequentialNumericKeys(): void {
    $annotations = [0 => 'first', 'key' => 'value', 1 => 'second'];
    $attribute = new McpTool(annotations: $annotations);
    $this->assertSame($annotations, $attribute->annotations);
  }

  /**
   * Tests acceptance of associative arrays with string keys.
   *
   * @covers ::__construct
   */
  public function testAcceptsAssociativeAnnotations(): void {
    $annotations = [
      'category' => 'content',
      'priority' => 'high',
      'stable' => TRUE,
    ];
    $attribute = new McpTool(annotations: $annotations);
    $this->assertSame($annotations, $attribute->annotations);
  }

  /**
   * Tests acceptance of nested array structures.
   *
   * @covers ::__construct
   */
  public function testAcceptsNestedAnnotations(): void {
    $annotations = [
      'metadata' => [
        'author' => 'test',
        'tags' => ['tag1', 'tag2'],
      ],
      'config' => [
        'timeout' => 30,
        'retry' => TRUE,
      ],
      'deep' => [
        'level1' => [
          'level2' => [
            'level3' => 'value',
          ],
        ],
      ],
    ];
    $attribute = new McpTool(annotations: $annotations);
    $this->assertSame($annotations, $attribute->annotations);
  }

  /**
   * Tests rejection of numeric string keys that form a sequential list.
   *
   * PHP's array_is_list() treats numeric string keys '0', '1', '2' as a list.
   *
   * @covers ::__construct
   */
  public function testRejectsNumericStringKeys(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('McpTool annotations must be an associative array');
    new McpTool(annotations: ['0' => 'zero', '1' => 'one', '2' => 'two']);
  }

  /**
   * Tests acceptance of annotations with special characters in keys.
   *
   * @covers ::__construct
   */
  public function testAcceptsSpecialCharactersInKeys(): void {
    $annotations = [
      'key-with-dash' => 'value1',
      'key.with.dot' => 'value2',
      'key_with_underscore' => 'value3',
      'key@with@symbols' => 'value4',
    ];
    $attribute = new McpTool(annotations: $annotations);
    $this->assertSame($annotations, $attribute->annotations);
  }

  /**
   * Tests title with special characters.
   *
   * @covers ::__construct
   */
  public function testTitleWithSpecialCharacters(): void {
    $title = 'Tool with "quotes" & <symbols>';
    $attribute = new McpTool(title: $title);
    $this->assertSame($title, $attribute->title);
  }

  /**
   * Tests that properties are readonly (immutable).
   *
   * @covers ::__construct
   */
  public function testReadonlyPropertyImmutability(): void {
    $attribute = new McpTool(title: 'Original Title');

    // Attempting to modify readonly property should cause an error.
    $this->expectException(\Error::class);
    $this->expectExceptionMessage('Cannot modify readonly property');
    $attribute->title = 'Modified Title';
  }

  /**
   * Tests that annotation property is readonly.
   *
   * @covers ::__construct
   */
  public function testAnnotationsReadonlyProperty(): void {
    $attribute = new McpTool(annotations: ['key' => 'value']);

    // Attempting to modify readonly property should cause an error.
    $this->expectException(\Error::class);
    $this->expectExceptionMessage('Cannot modify readonly property');
    $attribute->annotations = ['new' => 'data'];
  }

  /**
   * Tests reading attribute from a class using reflection.
   *
   * @covers ::__construct
   */
  public function testAttributeOnClass(): void {
    $reflection = new \ReflectionClass(TestClassWithMcpTool::class);
    $attributes = $reflection->getAttributes(McpTool::class);

    $this->assertCount(1, $attributes);

    $mcpToolAttribute = $attributes[0]->newInstance();
    $this->assertInstanceOf(McpTool::class, $mcpToolAttribute);
    $this->assertSame('Test Tool', $mcpToolAttribute->title);
    $this->assertSame(['category' => 'testing'], $mcpToolAttribute->annotations);
  }

  /**
   * Tests reading attribute without parameters from a class.
   *
   * @covers ::__construct
   */
  public function testAttributeOnClassWithDefaults(): void {
    $reflection = new \ReflectionClass(TestClassWithDefaultMcpTool::class);
    $attributes = $reflection->getAttributes(McpTool::class);

    $this->assertCount(1, $attributes);

    $mcpToolAttribute = $attributes[0]->newInstance();
    $this->assertInstanceOf(McpTool::class, $mcpToolAttribute);
    $this->assertNull($mcpToolAttribute->title);
    $this->assertNull($mcpToolAttribute->annotations);
  }

  /**
   * Tests attribute with large annotations array.
   *
   * @covers ::__construct
   */
  public function testWithLargeAnnotationsArray(): void {
    $annotations = [];
    for ($i = 0; $i < 100; $i++) {
      $annotations["key_$i"] = "value_$i";
    }
    $attribute = new McpTool(annotations: $annotations);
    $this->assertSame($annotations, $attribute->annotations);
    $this->assertCount(100, $attribute->annotations);
  }

}

/**
 * Test class with McpTool attribute for reflection testing.
 */
#[McpTool(title: 'Test Tool', annotations: ['category' => 'testing'])]
class TestClassWithMcpTool {
}

/**
 * Test class with default McpTool attribute for reflection testing.
 */
#[McpTool]
class TestClassWithDefaultMcpTool {
}
