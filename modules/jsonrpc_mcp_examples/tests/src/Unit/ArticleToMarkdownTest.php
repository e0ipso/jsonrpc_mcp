<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonrpc_mcp_examples\Unit;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jsonrpc\JsonRpcObject\ParameterBag;
use Drupal\jsonrpc_mcp_examples\Plugin\jsonrpc\Method\ArticleToMarkdown;
use Drupal\node\NodeInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for ArticleToMarkdown markdown conversion logic.
 *
 * These tests focus on the markdown conversion algorithm without requiring
 * full Drupal bootstrap or container initialization.
 *
 * @group jsonrpc_mcp_examples
 * @coversDefaultClass \Drupal\jsonrpc_mcp_examples\Plugin\jsonrpc\Method\ArticleToMarkdown
 */
class ArticleToMarkdownTest extends UnitTestCase {

  /**
   * The mocked entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected EntityTypeManagerInterface|MockObject $entityTypeManager;

  /**
   * The mocked node storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected EntityStorageInterface|MockObject $nodeStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->nodeStorage = $this->createMock(EntityStorageInterface::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityTypeManager->method('getStorage')
      ->with('node')
      ->willReturn($this->nodeStorage);
  }

  /**
   * Tests successful markdown conversion.
   *
   * @covers ::execute
   * @covers ::convertToMarkdown
   */
  public function testSuccessfulConversion(): void {
    $node = $this->createMock(NodeInterface::class);
    $node->method('id')->willReturn('1');
    $node->method('bundle')->willReturn('article');
    $node->method('access')->with('view')->willReturn(TRUE);
    $node->method('getTitle')->willReturn('Test Article');

    $bodyField = (object) ['value' => '<p>First paragraph.</p><p>Second paragraph.</p>'];
    $node->method('get')
      ->with('body')
      ->willReturn($bodyField);

    $this->nodeStorage->method('load')
      ->with(1)
      ->willReturn($node);

    $method = new ArticleToMarkdown(
      ['jsonrpc_request' => $this->createMockRequest()],
      'examples.article.toMarkdown',
      $this->createMockPluginDefinition(),
      $this->entityTypeManager
    );

    $params = new ParameterBag(['nid' => 1]);
    $result = $method->execute($params);

    $this->assertStringContainsString('# Test Article', $result);
    $this->assertStringContainsString('First paragraph.', $result);
    $this->assertStringContainsString('Second paragraph.', $result);
    $this->assertStringContainsString("First paragraph.\n\nSecond paragraph.", $result);
  }


  /**
   * Tests complex HTML stripping.
   *
   * @covers ::convertToMarkdown
   */
  public function testComplexHtmlStripping(): void {
    $node = $this->createMock(NodeInterface::class);
    $node->method('id')->willReturn('1');
    $node->method('bundle')->willReturn('article');
    $node->method('access')->with('view')->willReturn(TRUE);
    $node->method('getTitle')->willReturn('Complex Article');

    $bodyField = (object) ['value' => '<p>First <strong>bold</strong> paragraph.</p><p>Second <em>italic</em> paragraph.</p>'];
    $node->method('get')
      ->with('body')
      ->willReturn($bodyField);

    $this->nodeStorage->method('load')
      ->with(1)
      ->willReturn($node);

    $method = new ArticleToMarkdown(
      ['jsonrpc_request' => $this->createMockRequest()],
      'examples.article.toMarkdown',
      $this->createMockPluginDefinition(),
      $this->entityTypeManager
    );

    $params = new ParameterBag(['nid' => 1]);
    $result = $method->execute($params);

    // Check that HTML tags are stripped but text is preserved.
    $this->assertStringContainsString('First bold paragraph.', $result);
    $this->assertStringContainsString('Second italic paragraph.', $result);
    $this->assertStringNotContainsString('<strong>', $result);
    $this->assertStringNotContainsString('<em>', $result);
  }

  /**
   * Creates a mock plugin definition.
   */
  protected function createMockPluginDefinition() {
    return $this->createMock(\Drupal\jsonrpc\MethodInterface::class);
  }

  /**
   * Creates a mock JSON-RPC request.
   */
  protected function createMockRequest() {
    return $this->createMock(\Drupal\jsonrpc\JsonRpcObject\Request::class);
  }

}
