<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonrpc_mcp\Functional\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Tests\BrowserTestBase;
use GuzzleHttp\RequestOptions;

/**
 * Functional tests for the MCP tools discovery endpoint.
 *
 * Tests the /mcp/tools/list HTTP endpoint including JSON format, MCP
 * compliance, pagination, and access control integration.
 *
 * @group jsonrpc_mcp
 */
class McpToolsControllerTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'text',
    'jsonrpc',
    'jsonrpc_mcp',
    'jsonrpc_mcp_examples',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Grant anonymous users the necessary permissions to access MCP tools.
    user_role_grant_permissions('anonymous', [
      'access content',
      'access mcp tool discovery',
    ]);
  }

  /**
   * Tests comprehensive MCP endpoint behavior.
   *
   * This test consolidates all functional tests for the /mcp/tools/list
   * endpoint to reduce Drupal installation overhead while maintaining
   * complete test coverage.
   */
  public function testMcpEndpointBehavior(): void {
    // Section 1: Anonymous user tests - basic endpoint functionality.
    // Test endpoint accessibility and returns 200 status.
    $this->drupalGet('/mcp/tools/list');
    $this->assertSession()->statusCodeEquals(200);

    // Test response is valid JSON.
    $response = $this->getSession()->getPage()->getContent();
    $data = json_decode($response, TRUE);
    $this->assertIsArray($data, 'Response should be valid JSON');
    $this->assertSame(JSON_ERROR_NONE, json_last_error(), 'JSON should have no decoding errors');

    // Test response structure.
    $this->assertArrayHasKey('tools', $data, 'Response should have "tools" key');
    $this->assertArrayHasKey('nextCursor', $data, 'Response should have "nextCursor" key');
    $this->assertIsArray($data['tools'], '"tools" should be an array');

    // Test Content-Type header.
    $this->assertSession()->responseHeaderEquals('Content-Type', 'application/json');

    // Section 2: MCP schema compliance validation.
    // Verify all tools comply with MCP specification 2025-06-18.
    $this->assertNotEmpty($data['tools'], 'Should have at least one tool');

    foreach ($data['tools'] as $tool) {
      // Required fields per MCP specification.
      $this->assertArrayHasKey('name', $tool, 'Tool must have "name" field');
      $this->assertArrayHasKey('description', $tool, 'Tool must have "description" field');
      $this->assertArrayHasKey('inputSchema', $tool, 'Tool must have "inputSchema" field');

      // Validate field types.
      $this->assertIsString($tool['name'], '"name" should be a string');
      $this->assertIsString($tool['description'], '"description" should be a string');
      $this->assertIsArray($tool['inputSchema'], '"inputSchema" should be an object/array');
    }

    // Section 3: Example tool discovery and field mapping.
    // Find the examples.contentTypes.list tool for detailed validation.
    $example_tool = NULL;
    foreach ($data['tools'] as $tool) {
      if ($tool['name'] === 'examples.contentTypes.list') {
        $example_tool = $tool;
        break;
      }
    }

    // Test tool discovery.
    $tool_names = array_column($data['tools'], 'name');
    $this->assertContains('examples.contentTypes.list', $tool_names, 'Should discover examples.contentTypes.list method');
    $this->assertNotNull($example_tool, 'Should find examples.contentTypes.list tool');

    // Test name mapping (JSON-RPC id → MCP name).
    $this->assertEquals('examples.contentTypes.list', $example_tool['name'], 'Tool name should match JSON-RPC id');

    // Test description mapping (JSON-RPC usage → MCP description).
    $this->assertEquals('Lists all available content types', $example_tool['description']);

    // Test inputSchema format (JSON Schema compliance).
    $schema = $example_tool['inputSchema'];
    $this->assertEquals('object', $schema['type'], 'inputSchema type should be "object"');
    $this->assertArrayHasKey('properties', $schema, 'inputSchema should have "properties"');
    $this->assertIsArray($schema['properties'], '"properties" should be an array');

    // The "required" field is optional in JSON Schema and may not be present
    // if there are no required parameters.
    if (isset($schema['required'])) {
      $this->assertIsArray($schema['required'], '"required" should be an array if present');
    }

    // Test optional MCP fields.
    if (isset($example_tool['title'])) {
      $this->assertEquals('List Content Types', $example_tool['title']);
    }

    if (isset($example_tool['outputSchema'])) {
      $this->assertIsArray($example_tool['outputSchema']);
      $this->assertEquals('array', $example_tool['outputSchema']['type']);
    }

    if (isset($example_tool['annotations'])) {
      $this->assertIsArray($example_tool['annotations']);
    }

    // Section 4: Access control and filtering for anonymous users.
    // Verify example tools are visible to anonymous users with
    // 'access content'.
    $this->assertContains('examples.contentTypes.list', $tool_names);
    $this->assertContains('examples.articles.list', $tool_names);
    $this->assertContains('examples.article.toMarkdown', $tool_names);

    // Verify only MCP-marked tools are returned (all should start with
    // 'examples.').
    foreach ($tool_names as $tool_name) {
      $this->assertStringStartsWith('examples.', $tool_name, 'Only example methods with McpTool should be discovered');
    }

    // Section 5: Pagination behavior (if applicable).
    // Test pagination cursor generation.
    if ($data['nextCursor'] !== NULL) {
      $this->assertIsString($data['nextCursor'], 'nextCursor should be a string');
      $decoded = base64_decode($data['nextCursor'], TRUE);
      $this->assertNotFalse($decoded, 'nextCursor should be valid base64');
      $this->assertIsNumeric($decoded, 'Decoded cursor should be numeric offset');

      // Test pagination with cursor parameter.
      $this->drupalGet('/mcp/tools/list', ['query' => ['cursor' => $data['nextCursor']]]);
      $this->assertSession()->statusCodeEquals(200);

      $data2 = json_decode($this->getSession()->getPage()->getContent(), TRUE);
      $this->assertArrayHasKey('tools', $data2);
      $this->assertIsArray($data2['tools']);

      // Tools from second page should be different from first page.
      $names1 = array_column($data['tools'], 'name');
      $names2 = array_column($data2['tools'], 'name');
      $this->assertNotEquals($names1, $names2, 'Different pages should have different tools');

      // Follow pagination to the last page.
      $iterations = 0;
      $max_iterations = 10;
      $current_data = $data2;
      while ($current_data['nextCursor'] !== NULL && $iterations < $max_iterations) {
        $this->drupalGet('/mcp/tools/list', ['query' => ['cursor' => $current_data['nextCursor']]]);
        $current_data = json_decode($this->getSession()->getPage()->getContent(), TRUE);
        $iterations++;
      }

      // Last page should have null nextCursor.
      $this->assertNull($current_data['nextCursor'], 'Last page should have null nextCursor');
    }
    else {
      // All tools fit on one page.
      $this->assertNull($data['nextCursor'], 'nextCursor should be null when all tools fit on one page');
    }

    // Section 6: Authenticated user with permissions.
    // Create a user with both permissions.
    $user = $this->drupalCreateUser([
      'access content',
      'access mcp tool discovery',
    ]);
    $this->drupalLogin($user);

    $this->drupalGet('/mcp/tools/list');
    $user_data = json_decode($this->getSession()->getPage()->getContent(), TRUE);

    // User with permissions should see all example tools.
    $user_tool_names = array_column($user_data['tools'], 'name');
    $this->assertContains('examples.contentTypes.list', $user_tool_names);
    $this->assertContains('examples.articles.list', $user_tool_names);
    $this->assertContains('examples.article.toMarkdown', $user_tool_names);

    // Verify one of the tools has correct structure.
    $content_types_tool = NULL;
    foreach ($user_data['tools'] as $tool) {
      if ($tool['name'] === 'examples.contentTypes.list') {
        $content_types_tool = $tool;
        break;
      }
    }

    $this->assertNotNull($content_types_tool, 'Should find examples.contentTypes.list tool');
    $this->assertEquals('Lists all available content types', $content_types_tool['description']);
  }

  /**
   * Tests list endpoint returns 403 without permission.
   */
  public function testListEndpointPermissionDenied(): void {
    // Create user WITHOUT the MCP discovery permission.
    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);

    $this->drupalGet('/mcp/tools/list');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests describe endpoint returns tool details with permission.
   */
  public function testDescribeEndpointSuccess(): void {
    $user = $this->drupalCreateUser([
      'access content',
      'access mcp tool discovery',
    ]);
    $this->drupalLogin($user);

    $this->drupalGet('/mcp/tools/describe', [
      'query' => ['name' => 'examples.contentTypes.list'],
    ]);
    $this->assertSession()->statusCodeEquals(200);

    $response = $this->getSession()->getPage()->getContent();
    $data = json_decode($response, TRUE);

    $this->assertArrayHasKey('tool', $data);
    $tool = $data['tool'];

    $this->assertEquals('examples.contentTypes.list', $tool['name']);
    $this->assertEquals('Lists all available content types', $tool['description']);
    $this->assertArrayHasKey('inputSchema', $tool);
  }

  /**
   * Tests describe endpoint returns 403 without permission.
   */
  public function testDescribeEndpointPermissionDenied(): void {
    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);

    $this->drupalGet('/mcp/tools/describe', [
      'query' => ['name' => 'examples.contentTypes.list'],
    ]);
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests describe endpoint returns 404 for non-existent tool.
   */
  public function testDescribeEndpointToolNotFound(): void {
    $user = $this->drupalCreateUser([
      'access content',
      'access mcp tool discovery',
    ]);
    $this->drupalLogin($user);

    $this->drupalGet('/mcp/tools/describe', [
      'query' => ['name' => 'nonexistent.tool'],
    ]);
    $this->assertSession()->statusCodeEquals(404);

    $response = $this->getSession()->getPage()->getContent();
    $data = json_decode($response, TRUE);

    $this->assertArrayHasKey('error', $data);
    $this->assertEquals('tool_not_found', $data['error']['code']);
  }

  /**
   * Tests describe endpoint returns 400 for missing name parameter.
   */
  public function testDescribeEndpointMissingParameter(): void {
    $user = $this->drupalCreateUser([
      'access content',
      'access mcp tool discovery',
    ]);
    $this->drupalLogin($user);

    $this->drupalGet('/mcp/tools/describe');
    $this->assertSession()->statusCodeEquals(400);

    $response = $this->getSession()->getPage()->getContent();
    $data = json_decode($response, TRUE);

    $this->assertArrayHasKey('error', $data);
    $this->assertEquals('missing_parameter', $data['error']['code']);
  }

  /**
   * Tests invoke endpoint successfully executes a tool.
   */
  public function testInvokeEndpointSuccess(): void {
    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);

    $url = $this->buildUrl('/mcp/tools/invoke');
    $client = $this->getHttpClient();

    $response = $client->request('POST', $url, [
      RequestOptions::HTTP_ERRORS => FALSE,
      RequestOptions::COOKIES => $this->getSessionCookies(),
      RequestOptions::HEADERS => ['Content-Type' => 'application/json'],
      RequestOptions::BODY => Json::encode([
        'name' => 'examples.contentTypes.list',
        'arguments' => [],
      ]),
    ]);

    $this->assertEquals(200, $response->getStatusCode());

    $body = (string) $response->getBody();
    $data = Json::decode($body);
    $this->assertArrayHasKey('result', $data);
    $this->assertIsArray($data['result']);
  }

  /**
   * Tests invoke endpoint returns 404 for non-existent tool.
   */
  public function testInvokeEndpointToolNotFound(): void {
    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);

    $url = $this->buildUrl('/mcp/tools/invoke');
    $client = $this->getHttpClient();

    $response = $client->request('POST', $url, [
      RequestOptions::HTTP_ERRORS => FALSE,
      RequestOptions::COOKIES => $this->getSessionCookies(),
      RequestOptions::HEADERS => ['Content-Type' => 'application/json'],
      RequestOptions::BODY => Json::encode([
        'name' => 'nonexistent.tool',
        'arguments' => [],
      ]),
    ]);

    $this->assertEquals(404, $response->getStatusCode());

    $body = (string) $response->getBody();
    $this->assertNotEmpty($body, 'Response body should not be empty');
    $data = Json::decode($body);
    $this->assertArrayHasKey('error', $data);
    $this->assertEquals('tool_not_found', $data['error']['code']);
  }

  /**
   * Tests invoke endpoint returns 400 for malformed request.
   */
  public function testInvokeEndpointMalformedRequest(): void {
    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);

    $url = $this->buildUrl('/mcp/tools/invoke');
    $client = $this->getHttpClient();

    // Test missing 'name'.
    $response = $client->request('POST', $url, [
      RequestOptions::HTTP_ERRORS => FALSE,
      RequestOptions::COOKIES => $this->getSessionCookies(),
      RequestOptions::HEADERS => ['Content-Type' => 'application/json'],
      RequestOptions::BODY => Json::encode([
        'arguments' => [],
      ]),
    ]);

    $this->assertEquals(400, $response->getStatusCode());

    $body = (string) $response->getBody();
    $this->assertNotEmpty($body, 'Response body should not be empty');
    $data = Json::decode($body);
    $this->assertArrayHasKey('error', $data);
    $this->assertEquals('missing_parameter', $data['error']['code']);
  }

  /**
   * Tests invoke endpoint returns 400 for invalid JSON.
   */
  public function testInvokeEndpointInvalidJson(): void {
    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);

    $url = $this->buildUrl('/mcp/tools/invoke');
    $client = $this->getHttpClient();

    $response = $client->request('POST', $url, [
      RequestOptions::HTTP_ERRORS => FALSE,
      RequestOptions::COOKIES => $this->getSessionCookies(),
      RequestOptions::HEADERS => ['Content-Type' => 'application/json'],
      RequestOptions::BODY => '{invalid json}',
    ]);

    $this->assertEquals(400, $response->getStatusCode());

    $body = (string) $response->getBody();
    $this->assertNotEmpty($body, 'Response body should not be empty');
    $data = Json::decode($body);
    $this->assertArrayHasKey('error', $data);
    $this->assertEquals('invalid_json', $data['error']['code']);
  }

}
