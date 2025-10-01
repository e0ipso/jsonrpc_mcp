<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonrpc_mcp\Unit\Attribute;

use Drupal\jsonrpc_mcp\Attribute\McpTool;
use Drupal\Tests\jsonrpc_mcp\Unit\Attribute\Fixtures\TestClassWithDefaultMcpTool;
use Drupal\Tests\jsonrpc_mcp\Unit\Attribute\Fixtures\TestClassWithMcpTool;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the McpTool attribute class.
 *
 * @group jsonrpc_mcp
 * @coversDefaultClass \Drupal\jsonrpc_mcp\Attribute\McpTool
 */
class McpToolTest extends TestCase {

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
   * Tests rejection of indexed arrays (lists) for annotations.
   *
   * This validates the business rule that annotations must be associative
   * arrays with string keys, not sequential numeric indexes.
   *
   * @covers ::__construct
   */
  public function testRejectsListAnnotations(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('McpTool annotations must be an associative array');
    new McpTool(annotations: ['item1', 'item2', 'item3']);
  }

  /**
   * Tests reading attribute from a class using reflection.
   *
   * This validates integration with PHP's attribute system.
   *
   * @covers ::__construct
   */
  public function testAttributeOnClass(): void {
    $reflection = new \ReflectionClass(TestClassWithMcpTool::class);
    $attributes = $reflection->getAttributes(McpTool::class);

    $this->assertCount(1, $attributes);

    $mcpToolAttribute = $attributes[0]->newInstance();
    $this->assertSame('Test Tool', $mcpToolAttribute->title);
    $this->assertSame(['category' => 'testing'], $mcpToolAttribute->annotations);
  }

}
