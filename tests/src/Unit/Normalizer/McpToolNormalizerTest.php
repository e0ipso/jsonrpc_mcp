<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonrpc_mcp\Unit\Normalizer;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\jsonrpc\Attribute\JsonRpcParameterDefinition;
use Drupal\jsonrpc\MethodInterface;
use Drupal\jsonrpc_mcp\Normalizer\McpToolNormalizer;
use Drupal\Tests\jsonrpc_mcp\Unit\Normalizer\Fixtures\TestMethodWithMcpTool;
use Drupal\Tests\jsonrpc_mcp\Unit\Normalizer\Fixtures\TestMethodWithOutputSchema;
use Drupal\Tests\jsonrpc_mcp\Unit\Normalizer\Fixtures\TestMethodWithoutMcpTool;
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
   * Tests normalization with minimal method (only required fields).
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
      ->willReturn(new TranslatableMarkup('Test description'));
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
   * Tests normalization with McpTool title.
   *
   * @covers ::normalize
   * @covers ::extractMcpToolData
   */
  public function testNormalizeWithTitle(): void {
    $method = $this->createMock(MethodInterface::class);
    $method->method('id')
      ->willReturn('test.method');
    $method->method('getUsage')
      ->willReturn(new TranslatableMarkup('Test description'));
    $method->method('getParams')
      ->willReturn([]);
    $method->method('getClass')
      ->willReturn(TestMethodWithMcpTool::class);

    $result = $this->normalizer->normalize($method);

    $this->assertSame('Test Method Title', $result['title']);
    $this->assertSame('test.method', $result['name']);
  }

  /**
   * Tests normalization with McpTool annotations.
   *
   * @covers ::normalize
   * @covers ::extractMcpToolData
   */
  public function testNormalizeWithAnnotations(): void {
    $method = $this->createMock(MethodInterface::class);
    $method->method('id')
      ->willReturn('test.method');
    $method->method('getUsage')
      ->willReturn(new TranslatableMarkup('Test description'));
    $method->method('getParams')
      ->willReturn([]);
    $method->method('getClass')
      ->willReturn(TestMethodWithMcpTool::class);

    $result = $this->normalizer->normalize($method);

    $this->assertArrayHasKey('annotations', $result);
    $this->assertSame(['category' => 'test', 'priority' => 'high'], $result['annotations']);
  }

  /**
   * Tests normalization with outputSchema.
   *
   * @covers ::normalize
   */
  public function testNormalizeWithOutputSchema(): void {
    $method = $this->createMock(MethodInterface::class);
    $method->method('id')
      ->willReturn('test.method');
    $method->method('getUsage')
      ->willReturn(new TranslatableMarkup('Test description'));
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
   * Tests inputSchema generation with empty parameters.
   *
   * @covers ::buildInputSchema
   */
  public function testBuildInputSchemaEmpty(): void {
    $method = $this->createMock(MethodInterface::class);
    $method->method('id')
      ->willReturn('test.method');
    $method->method('getUsage')
      ->willReturn(new TranslatableMarkup('Test'));
    $method->method('getParams')
      ->willReturn([]);
    $method->method('getClass')
      ->willReturn(NULL);

    $result = $this->normalizer->normalize($method);

    $this->assertSame([
      'type' => 'object',
      'properties' => [],
    ], $result['inputSchema']);
  }

  /**
   * Tests inputSchema generation with required parameters.
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
      ->willReturn(new TranslatableMarkup('Test'));
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
   * Tests inputSchema generation with optional parameters.
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
      ->willReturn(new TranslatableMarkup('Test'));
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
   * Tests inputSchema generation with parameter descriptions.
   *
   * @covers ::buildInputSchema
   */
  public function testBuildInputSchemaWithDescriptions(): void {
    $param = new JsonRpcParameterDefinition(
      id: 'input',
      schema: ['type' => 'string'],
      description: new TranslatableMarkup('The input value'),
      required: TRUE,
    );

    $method = $this->createMock(MethodInterface::class);
    $method->method('id')
      ->willReturn('test.method');
    $method->method('getUsage')
      ->willReturn(new TranslatableMarkup('Test'));
    $method->method('getParams')
      ->willReturn(['input' => $param]);
    $method->method('getClass')
      ->willReturn(NULL);

    $result = $this->normalizer->normalize($method);

    $this->assertArrayHasKey('input', $result['inputSchema']['properties']);
    $this->assertSame([
      'type' => 'string',
      'description' => 'The input value',
    ], $result['inputSchema']['properties']['input']);
  }

  /**
   * Tests TranslatableMarkup conversion to string.
   *
   * @covers ::normalize
   */
  public function testTranslatableMarkupConversion(): void {
    $translatable = new TranslatableMarkup('Translatable text with @placeholder', [
      '@placeholder' => 'value',
    ]);

    $method = $this->createMock(MethodInterface::class);
    $method->method('id')
      ->willReturn('test.method');
    $method->method('getUsage')
      ->willReturn($translatable);
    $method->method('getParams')
      ->willReturn([]);
    $method->method('getClass')
      ->willReturn(NULL);

    $result = $this->normalizer->normalize($method);

    $this->assertIsString($result['description']);
    $this->assertSame('Translatable text with value', $result['description']);
  }

  /**
   * Tests nested parameter schemas.
   *
   * @covers ::buildInputSchema
   */
  public function testNestedParameterSchemas(): void {
    $param = new JsonRpcParameterDefinition(
      id: 'complex',
      schema: [
        'type' => 'object',
        'properties' => [
          'nested' => [
            'type' => 'object',
            'properties' => [
              'deep' => ['type' => 'string'],
            ],
          ],
        ],
      ],
      required: TRUE,
    );

    $method = $this->createMock(MethodInterface::class);
    $method->method('id')
      ->willReturn('test.method');
    $method->method('getUsage')
      ->willReturn(new TranslatableMarkup('Test'));
    $method->method('getParams')
      ->willReturn(['complex' => $param]);
    $method->method('getClass')
      ->willReturn(NULL);

    $result = $this->normalizer->normalize($method);

    $this->assertArrayHasKey('complex', $result['inputSchema']['properties']);
    $this->assertSame('object', $result['inputSchema']['properties']['complex']['type']);
    $this->assertArrayHasKey('nested', $result['inputSchema']['properties']['complex']['properties']);
  }

  /**
   * Tests method without McpTool attribute (fallback behavior).
   *
   * @covers ::normalize
   * @covers ::extractMcpToolData
   */
  public function testMethodWithoutMcpToolAttribute(): void {
    $method = $this->createMock(MethodInterface::class);
    $method->method('id')
      ->willReturn('test.method');
    $method->method('getUsage')
      ->willReturn(new TranslatableMarkup('Test description'));
    $method->method('getParams')
      ->willReturn([]);
    $method->method('getClass')
      ->willReturn(TestMethodWithoutMcpTool::class);

    $result = $this->normalizer->normalize($method);

    $this->assertArrayNotHasKey('title', $result);
    $this->assertArrayNotHasKey('annotations', $result);
    $this->assertSame('test.method', $result['name']);
    $this->assertSame('Test description', $result['description']);
  }

  /**
   * Tests normalization with all features combined.
   *
   * @covers ::normalize
   * @covers ::buildInputSchema
   * @covers ::extractMcpToolData
   */
  public function testNormalizeWithAllFeatures(): void {
    $param1 = new JsonRpcParameterDefinition(
      id: 'required_param',
      schema: ['type' => 'string'],
      description: new TranslatableMarkup('Required parameter'),
      required: TRUE,
    );
    $param2 = new JsonRpcParameterDefinition(
      id: 'optional_param',
      schema: ['type' => 'integer', 'minimum' => 0],
      description: new TranslatableMarkup('Optional parameter'),
      required: FALSE,
    );

    $method = $this->createMock(MethodInterface::class);
    $method->method('id')
      ->willReturn('complete.method');
    $method->method('getUsage')
      ->willReturn(new TranslatableMarkup('Complete method with all features'));
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

  /**
   * Tests outputSchema is not added when method doesn't implement it.
   *
   * @covers ::normalize
   */
  public function testNoOutputSchemaWhenNotImplemented(): void {
    $method = $this->createMock(MethodInterface::class);
    $method->method('id')
      ->willReturn('test.method');
    $method->method('getUsage')
      ->willReturn(new TranslatableMarkup('Test'));
    $method->method('getParams')
      ->willReturn([]);
    $method->method('getClass')
      ->willReturn(TestMethodWithoutMcpTool::class);

    $result = $this->normalizer->normalize($method);

    $this->assertArrayNotHasKey('outputSchema', $result);
  }

  /**
   * Tests outputSchema is not added when it returns null.
   *
   * @covers ::normalize
   */
  public function testNoOutputSchemaWhenNull(): void {
    $method = $this->createMock(MethodInterface::class);
    $method->method('id')
      ->willReturn('test.method');
    $method->method('getUsage')
      ->willReturn(new TranslatableMarkup('Test'));
    $method->method('getParams')
      ->willReturn([]);
    $method->method('getClass')
      ->willReturn(TestMethodWithMcpTool::class);

    $result = $this->normalizer->normalize($method);

    // TestMethodWithMcpTool doesn't have outputSchema method.
    $this->assertArrayNotHasKey('outputSchema', $result);
  }

  /**
   * Tests parameter without description.
   *
   * @covers ::buildInputSchema
   */
  public function testParameterWithoutDescription(): void {
    $param = new JsonRpcParameterDefinition(
      id: 'simple',
      schema: ['type' => 'boolean'],
      required: TRUE,
    );

    $method = $this->createMock(MethodInterface::class);
    $method->method('id')
      ->willReturn('test.method');
    $method->method('getUsage')
      ->willReturn(new TranslatableMarkup('Test'));
    $method->method('getParams')
      ->willReturn(['simple' => $param]);
    $method->method('getClass')
      ->willReturn(NULL);

    $result = $this->normalizer->normalize($method);

    $this->assertSame(['type' => 'boolean'], $result['inputSchema']['properties']['simple']);
    $this->assertArrayNotHasKey('description', $result['inputSchema']['properties']['simple']);
  }

  /**
   * Tests array parameter schema.
   *
   * @covers ::buildInputSchema
   */
  public function testArrayParameterSchema(): void {
    $param = new JsonRpcParameterDefinition(
      id: 'items',
      schema: [
        'type' => 'array',
        'items' => ['type' => 'string'],
      ],
      description: new TranslatableMarkup('List of items'),
      required: TRUE,
    );

    $method = $this->createMock(MethodInterface::class);
    $method->method('id')
      ->willReturn('test.method');
    $method->method('getUsage')
      ->willReturn(new TranslatableMarkup('Test'));
    $method->method('getParams')
      ->willReturn(['items' => $param]);
    $method->method('getClass')
      ->willReturn(NULL);

    $result = $this->normalizer->normalize($method);

    $this->assertSame([
      'type' => 'array',
      'items' => ['type' => 'string'],
      'description' => 'List of items',
    ], $result['inputSchema']['properties']['items']);
  }

  /**
   * Tests multiple parameters with mixed requirements.
   *
   * @covers ::buildInputSchema
   */
  public function testMultipleParametersMixedRequirements(): void {
    $params = [
      'required1' => new JsonRpcParameterDefinition(
        id: 'required1',
        schema: ['type' => 'string'],
        required: TRUE,
      ),
      'optional1' => new JsonRpcParameterDefinition(
        id: 'optional1',
        schema: ['type' => 'string'],
        required: FALSE,
      ),
      'required2' => new JsonRpcParameterDefinition(
        id: 'required2',
        schema: ['type' => 'number'],
        required: TRUE,
      ),
      'optional2' => new JsonRpcParameterDefinition(
        id: 'optional2',
        schema: ['type' => 'boolean'],
        required: FALSE,
      ),
    ];

    $method = $this->createMock(MethodInterface::class);
    $method->method('id')
      ->willReturn('test.method');
    $method->method('getUsage')
      ->willReturn(new TranslatableMarkup('Test'));
    $method->method('getParams')
      ->willReturn($params);
    $method->method('getClass')
      ->willReturn(NULL);

    $result = $this->normalizer->normalize($method);

    $this->assertCount(4, $result['inputSchema']['properties']);
    $this->assertSame(['required1', 'required2'], $result['inputSchema']['required']);
  }

  /**
   * Tests that method class being NULL doesn't cause errors.
   *
   * @covers ::normalize
   * @covers ::extractMcpToolData
   */
  public function testNullMethodClass(): void {
    $method = $this->createMock(MethodInterface::class);
    $method->method('id')
      ->willReturn('test.method');
    $method->method('getUsage')
      ->willReturn(new TranslatableMarkup('Test'));
    $method->method('getParams')
      ->willReturn([]);
    $method->method('getClass')
      ->willReturn(NULL);

    $result = $this->normalizer->normalize($method);

    $this->assertArrayNotHasKey('title', $result);
    $this->assertArrayNotHasKey('annotations', $result);
    $this->assertArrayNotHasKey('outputSchema', $result);
  }

}
