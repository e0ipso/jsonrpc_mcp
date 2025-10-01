<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonrpc_mcp_examples\Unit;

use Drupal\jsonrpc\MethodInterface;
use Drupal\jsonrpc\JsonRpcObject\Request;
use Drupal\Core\Entity\EntityTypeManagerInterface;
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
    // Use an anonymous class to create a field object with a public
    // value property.
    $bodyField = new class($body) {

      public function __construct(public mixed $value) {}

    };

    $node = $this->createMock(NodeInterface::class);
    $node->method('getTitle')->willReturn($title);
    $node->method('get')->with('body')->willReturn($bodyField);

    return $node;
  }

  /**
   * Creates an instance of ArticleToMarkdown for testing.
   */
  protected function createMethod(): ArticleToMarkdown {
    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);

    // Create a mock Request object required by JsonRpcMethodBase.
    $request = $this->createMock(Request::class);

    // Configuration must include the jsonrpc_request key.
    $configuration = [
      'jsonrpc_request' => $request,
    ];

    return new ArticleToMarkdown(
      $configuration,
      'examples.article.toMarkdown',
      $this->createMock(MethodInterface::class),
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
