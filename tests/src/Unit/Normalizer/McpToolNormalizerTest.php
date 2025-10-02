<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonrpc_mcp\Unit\Normalizer;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\jsonrpc\Attribute\JsonRpcParameterDefinition;
use Drupal\jsonrpc\MethodInterface;
use Drupal\jsonrpc_mcp\Normalizer\McpToolNormalizer;
use Drupal\Tests\jsonrpc_mcp\Unit\Normalizer\Fixtures\TestMethodWithMcpTool;
use Drupal\Tests\jsonrpc_mcp\Unit\Normalizer\Fixtures\TestMethodWithOutputSchema;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the McpToolNormalizer class.
 *
 * @group jsonrpc_mcp
 * @coversDefaultClass \Drupal\jsonrpc_mcp\Normalizer\McpToolNormalizer
 */
class McpToolNormalizerTest extends TestCase {

  /**
   * The normalizer under test.
   */
  protected McpToolNormalizer $normalizer;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->normalizer = new McpToolNormalizer();
  }

  /**
   * Tests JSON-RPC to MCP schema transformation with minimal method.
   *
   * Validates the core transformation logic from JSON-RPC method definition
   * to MCP tool schema format.
   *
   * @covers ::normalize
   * @covers ::buildInputSchema
   * @covers ::extractMcpToolData
   */
  public function testNormalizeWithMinimalMethod(): void {
    $method = $this->createMock(MethodInterface::class);
    $method->method('id')
      ->willReturn('test.method');
    $method->method('getUsage')
      ->willReturn('Test description');
    $method->method('getParams')
      ->willReturn([]);
    $method->method('getClass')
      ->willReturn(NULL);

    $result = $this->normalizer->normalize($method);

    $this->assertSame('test.method', $result['name']);
    $this->assertSame('Test description', $result['description']);
    $this->assertArrayHasKey('inputSchema', $result);
    $this->assertSame('object', $result['inputSchema']['type']);
    $this->assertSame([], $result['inputSchema']['properties']);
    $this->assertArrayNotHasKey('required', $result['inputSchema']);
    $this->assertArrayNotHasKey('title', $result);
    $this->assertArrayNotHasKey('annotations', $result);
    $this->assertArrayNotHasKey('outputSchema', $result);
  }

  /**
   * Tests normalization with McpTool title mapping.
   *
   * Validates that the title from the McpTool attribute is correctly
   * included in the MCP tool schema.
   *
   * @covers ::normalize
   * @covers ::extractMcpToolData
   */
  public function testNormalizeWithTitle(): void {
    $method = $this->createMock(MethodInterface::class);
    $method->method('id')
      ->willReturn('test.method');
    $method->method('getUsage')
      ->willReturn('Test description');
    $method->method('getParams')
      ->willReturn([]);
    $method->method('getClass')
      ->willReturn(TestMethodWithMcpTool::class);

    $result = $this->normalizer->normalize($method);

    $this->assertSame('Test Method Title', $result['title']);
    $this->assertSame('test.method', $result['name']);
  }

  /**
   * Tests normalization with McpTool annotations mapping.
   *
   * Validates that annotations from the McpTool attribute are correctly
   * transformed and included in the MCP tool schema.
   *
   * @covers ::normalize
   * @covers ::extractMcpToolData
   */
  public function testNormalizeWithAnnotations(): void {
    $method = $this->createMock(MethodInterface::class);
    $method->method('id')
      ->willReturn('test.method');
    $method->method('getUsage')
      ->willReturn('Test description');
    $method->method('getParams')
      ->willReturn([]);
    $method->method('getClass')
      ->willReturn(TestMethodWithMcpTool::class);

    $result = $this->normalizer->normalize($method);

    $this->assertArrayHasKey('annotations', $result);
    $this->assertSame(['category' => 'test', 'priority' => 'high'], $result['annotations']);
  }

  /**
   * Tests normalization with outputSchema inclusion.
   *
   * Validates that outputSchema is correctly extracted and included
   * in the MCP tool schema when the method implements it.
   *
   * @covers ::normalize
   */
  public function testNormalizeWithOutputSchema(): void {
    $method = $this->createMock(MethodInterface::class);
    $method->method('id')
      ->willReturn('test.method');
    $method->method('getUsage')
      ->willReturn('Test description');
    $method->method('getParams')
      ->willReturn([]);
    $method->method('getClass')
      ->willReturn(TestMethodWithOutputSchema::class);

    $result = $this->normalizer->normalize($method);

    $this->assertArrayHasKey('outputSchema', $result);
    $this->assertSame([
      'type' => 'object',
      'properties' => [
        'result' => ['type' => 'string'],
      ],
    ], $result['outputSchema']);
  }

  /**
   * Tests parameter mapping with required parameters.
   *
   * Validates that required parameters are correctly mapped to the
   * inputSchema 'required' array in the MCP tool schema.
   *
   * @covers ::buildInputSchema
   */
  public function testBuildInputSchemaWithRequiredParams(): void {
    $param1 = new JsonRpcParameterDefinition(
      id: 'name',
      schema: ['type' => 'string'],
      required: TRUE,
    );
    $param2 = new JsonRpcParameterDefinition(
      id: 'age',
      schema: ['type' => 'integer'],
      required: TRUE,
    );

    $method = $this->createMock(MethodInterface::class);
    $method->method('id')
      ->willReturn('test.method');
    $method->method('getUsage')
      ->willReturn('Test');
    $method->method('getParams')
      ->willReturn(['name' => $param1, 'age' => $param2]);
    $method->method('getClass')
      ->willReturn(NULL);

    $result = $this->normalizer->normalize($method);

    $this->assertSame('object', $result['inputSchema']['type']);
    $this->assertArrayHasKey('name', $result['inputSchema']['properties']);
    $this->assertArrayHasKey('age', $result['inputSchema']['properties']);
    $this->assertSame(['type' => 'string'], $result['inputSchema']['properties']['name']);
    $this->assertSame(['type' => 'integer'], $result['inputSchema']['properties']['age']);
    $this->assertSame(['name', 'age'], $result['inputSchema']['required']);
  }

  /**
   * Tests parameter mapping with optional vs required distinction.
   *
   * Validates the business rule that only required parameters are
   * included in the 'required' array of the inputSchema.
   *
   * @covers ::buildInputSchema
   */
  public function testBuildInputSchemaWithOptionalParams(): void {
    $param1 = new JsonRpcParameterDefinition(
      id: 'required_field',
      schema: ['type' => 'string'],
      required: TRUE,
    );
    $param2 = new JsonRpcParameterDefinition(
      id: 'optional_field',
      schema: ['type' => 'string'],
      required: FALSE,
    );

    $method = $this->createMock(MethodInterface::class);
    $method->method('id')
      ->willReturn('test.method');
    $method->method('getUsage')
      ->willReturn('Test');
    $method->method('getParams')
      ->willReturn(['required_field' => $param1, 'optional_field' => $param2]);
    $method->method('getClass')
      ->willReturn(NULL);

    $result = $this->normalizer->normalize($method);

    $this->assertArrayHasKey('required_field', $result['inputSchema']['properties']);
    $this->assertArrayHasKey('optional_field', $result['inputSchema']['properties']);
    $this->assertSame(['required_field'], $result['inputSchema']['required']);
  }


  /**
   * Tests comprehensive JSON-RPC to MCP transformation with all features.
   *
   * Integration test validating the complete transformation pipeline:
   * - JSON-RPC method -> MCP tool schema
   * - Required vs optional parameters
   * - McpTool attribute mapping (title, annotations)
   * - outputSchema inclusion.
   *
   * @covers ::normalize
   * @covers ::buildInputSchema
   * @covers ::extractMcpToolData
   */
  public function testNormalizeWithAllFeatures(): void {
    $param1 = new JsonRpcParameterDefinition(
      id: 'required_param',
      schema: ['type' => 'string'],
      description: 'Required parameter',
      required: TRUE,
    );
    $param2 = new JsonRpcParameterDefinition(
      id: 'optional_param',
      schema: ['type' => 'integer', 'minimum' => 0],
      description: 'Optional parameter',
      required: FALSE,
    );

    $method = $this->createMock(MethodInterface::class);
    $method->method('id')
      ->willReturn('complete.method');
    $method->method('getUsage')
      ->willReturn('Complete method with all features');
    $method->method('getParams')
      ->willReturn(['required_param' => $param1, 'optional_param' => $param2]);
    $method->method('getClass')
      ->willReturn(TestMethodWithOutputSchema::class);

    $result = $this->normalizer->normalize($method);

    // Check base fields.
    $this->assertSame('complete.method', $result['name']);
    $this->assertSame('Complete method with all features', $result['description']);

    // Check inputSchema.
    $this->assertSame('object', $result['inputSchema']['type']);
    $this->assertCount(2, $result['inputSchema']['properties']);
    $this->assertSame(['type' => 'string', 'description' => 'Required parameter'], $result['inputSchema']['properties']['required_param']);
    $this->assertSame(['type' => 'integer', 'minimum' => 0, 'description' => 'Optional parameter'], $result['inputSchema']['properties']['optional_param']);
    $this->assertSame(['required_param'], $result['inputSchema']['required']);

    // Check outputSchema.
    $this->assertArrayHasKey('outputSchema', $result);
    $this->assertSame([
      'type' => 'object',
      'properties' => [
        'result' => ['type' => 'string'],
      ],
    ], $result['outputSchema']);

    // Check McpTool data.
    $this->assertSame('Test Method With Output', $result['title']);
    $this->assertSame(['test' => TRUE], $result['annotations']);
  }

}
