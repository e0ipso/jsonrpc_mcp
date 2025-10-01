<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonrpc_mcp\Functional\Controller;

use Drupal\Tests\BrowserTestBase;

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
    'jsonrpc',
    'jsonrpc_mcp',
    'jsonrpc_mcp_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that the tools list endpoint exists and is accessible.
   */
  public function testToolsListEndpointExists(): void {
    $this->drupalGet('/mcp/tools/list');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests that the tools list endpoint returns valid JSON.
   */
  public function testToolsListReturnsJson(): void {
    $this->drupalGet('/mcp/tools/list');
    $this->assertSession()->statusCodeEquals(200);

    $response = $this->getSession()->getPage()->getContent();
    $data = json_decode($response, TRUE);

    $this->assertIsArray($data, 'Response should be valid JSON');
    $this->assertNull(json_last_error(), 'JSON should have no decoding errors');
  }

  /**
   * Tests that the response has the correct structure.
   */
  public function testToolsListStructure(): void {
    $this->drupalGet('/mcp/tools/list');
    $data = json_decode($this->getSession()->getPage()->getContent(), TRUE);

    $this->assertArrayHasKey('tools', $data, 'Response should have "tools" key');
    $this->assertArrayHasKey('nextCursor', $data, 'Response should have "nextCursor" key');
    $this->assertIsArray($data['tools'], '"tools" should be an array');
  }

  /**
   * Tests that each tool complies with MCP schema requirements.
   */
  public function testToolSchemaCompliance(): void {
    $this->drupalGet('/mcp/tools/list');
    $data = json_decode($this->getSession()->getPage()->getContent(), TRUE);

    $this->assertNotEmpty($data['tools'], 'Should have at least one tool');

    foreach ($data['tools'] as $tool) {
      // Required fields per MCP specification 2025-06-18.
      $this->assertArrayHasKey('name', $tool, 'Tool must have "name" field');
      $this->assertArrayHasKey('description', $tool, 'Tool must have "description" field');
      $this->assertArrayHasKey('inputSchema', $tool, 'Tool must have "inputSchema" field');

      // Validate field types.
      $this->assertIsString($tool['name'], '"name" should be a string');
      $this->assertIsString($tool['description'], '"description" should be a string');
      $this->assertIsArray($tool['inputSchema'], '"inputSchema" should be an object/array');
    }
  }

  /**
   * Tests that tool id is mapped to name field.
   */
  public function testToolNameMapping(): void {
    $this->drupalGet('/mcp/tools/list');
    $data = json_decode($this->getSession()->getPage()->getContent(), TRUE);

    // Find the test.example tool.
    $test_tool = NULL;
    foreach ($data['tools'] as $tool) {
      if ($tool['name'] === 'test.example') {
        $test_tool = $tool;
        break;
      }
    }

    $this->assertNotNull($test_tool, 'Should find test.example tool');
    $this->assertEquals('test.example', $test_tool['name'], 'Tool name should match JSON-RPC id');
  }

  /**
   * Tests that usage is mapped to description field.
   */
  public function testToolDescriptionMapping(): void {
    $this->drupalGet('/mcp/tools/list');
    $data = json_decode($this->getSession()->getPage()->getContent(), TRUE);

    // Find the test.example tool.
    $test_tool = NULL;
    foreach ($data['tools'] as $tool) {
      if ($tool['name'] === 'test.example') {
        $test_tool = $tool;
        break;
      }
    }

    $this->assertNotNull($test_tool, 'Should find test.example tool');
    $this->assertEquals('Test method for MCP discovery', $test_tool['description']);
  }

  /**
   * Tests that inputSchema is valid JSON Schema format.
   */
  public function testInputSchemaFormat(): void {
    $this->drupalGet('/mcp/tools/list');
    $data = json_decode($this->getSession()->getPage()->getContent(), TRUE);

    // Find the test.example tool.
    $test_tool = NULL;
    foreach ($data['tools'] as $tool) {
      if ($tool['name'] === 'test.example') {
        $test_tool = $tool;
        break;
      }
    }

    $this->assertNotNull($test_tool, 'Should find test.example tool');

    $schema = $test_tool['inputSchema'];
    $this->assertEquals('object', $schema['type'], 'inputSchema type should be "object"');
    $this->assertArrayHasKey('properties', $schema, 'inputSchema should have "properties"');
    $this->assertIsArray($schema['properties'], '"properties" should be an array');
    $this->assertArrayHasKey('required', $schema, 'inputSchema should have "required" field');
    $this->assertIsArray($schema['required'], '"required" should be an array');
  }

  /**
   * Tests pagination with cursor parameter.
   */
  public function testPaginationWithCursor(): void {
    // First page (no cursor).
    $this->drupalGet('/mcp/tools/list');
    $data1 = json_decode($this->getSession()->getPage()->getContent(), TRUE);

    // If there's a next cursor, test it.
    if ($data1['nextCursor'] !== NULL) {
      $this->drupalGet('/mcp/tools/list', ['query' => ['cursor' => $data1['nextCursor']]]);
      $this->assertSession()->statusCodeEquals(200);

      $data2 = json_decode($this->getSession()->getPage()->getContent(), TRUE);
      $this->assertArrayHasKey('tools', $data2);
      $this->assertIsArray($data2['tools']);

      // Tools from second page should be different from first page.
      $names1 = array_column($data1['tools'], 'name');
      $names2 = array_column($data2['tools'], 'name');
      $this->assertNotEquals($names1, $names2, 'Different pages should have different tools');
    }
    else {
      // All tools fit on one page.
      $this->assertNull($data1['nextCursor'], 'nextCursor should be null when all tools fit on one page');
    }
  }

  /**
   * Tests that nextCursor is generated correctly.
   */
  public function testPaginationNextCursor(): void {
    $this->drupalGet('/mcp/tools/list');
    $data = json_decode($this->getSession()->getPage()->getContent(), TRUE);

    // nextCursor should either be null or a valid base64 string.
    if ($data['nextCursor'] !== NULL) {
      $this->assertIsString($data['nextCursor'], 'nextCursor should be a string');
      $decoded = base64_decode($data['nextCursor'], TRUE);
      $this->assertNotFalse($decoded, 'nextCursor should be valid base64');
      $this->assertIsNumeric($decoded, 'Decoded cursor should be numeric offset');
    }
  }

  /**
   * Tests that nextCursor is null on the last page.
   */
  public function testPaginationLastPage(): void {
    $this->drupalGet('/mcp/tools/list');
    $data = json_decode($this->getSession()->getPage()->getContent(), TRUE);

    // Follow pagination to the last page.
    $iterations = 0;
    $max_iterations = 10;
    while ($data['nextCursor'] !== NULL && $iterations < $max_iterations) {
      $this->drupalGet('/mcp/tools/list', ['query' => ['cursor' => $data['nextCursor']]]);
      $data = json_decode($this->getSession()->getPage()->getContent(), TRUE);
      $iterations++;
    }

    // Last page should have null nextCursor.
    $this->assertNull($data['nextCursor'], 'Last page should have null nextCursor');
  }

  /**
   * Tests that test plugin appears in discovery results.
   */
  public function testDiscoveryIncludesTestMethod(): void {
    $this->drupalGet('/mcp/tools/list');
    $data = json_decode($this->getSession()->getPage()->getContent(), TRUE);

    $tool_names = array_column($data['tools'], 'name');
    $this->assertContains('test.example', $tool_names, 'Should discover test.example method');
  }

  /**
   * Tests empty response when no tools are available.
   *
   * This test verifies behavior when a user has no access to any tools.
   * The test module includes an admin-only method that won't be visible
   * to anonymous users.
   */
  public function testEmptyToolsForRestrictedUser(): void {
    $this->drupalGet('/mcp/tools/list');
    $data = json_decode($this->getSession()->getPage()->getContent(), TRUE);

    // Anonymous users should not see admin-only tools.
    $tool_names = array_column($data['tools'], 'name');
    $this->assertNotContains('test.adminOnly', $tool_names, 'Anonymous users should not see admin-only tools');
  }

  /**
   * Tests that admin tools are visible to users with proper permissions.
   */
  public function testAccessControlFiltering(): void {
    // Create a user with admin permissions.
    $admin_user = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($admin_user);

    $this->drupalGet('/mcp/tools/list');
    $data = json_decode($this->getSession()->getPage()->getContent(), TRUE);

    // Admin users should see admin-only tools.
    $tool_names = array_column($data['tools'], 'name');
    $this->assertContains('test.adminOnly', $tool_names, 'Admin users should see admin-only tools');

    // Verify the admin tool has correct structure.
    $admin_tool = NULL;
    foreach ($data['tools'] as $tool) {
      if ($tool['name'] === 'test.adminOnly') {
        $admin_tool = $tool;
        break;
      }
    }

    $this->assertNotNull($admin_tool, 'Should find test.adminOnly tool');
    $this->assertEquals('Admin-only test method', $admin_tool['description']);
  }

  /**
   * Tests that optional MCP fields are included when present.
   */
  public function testOptionalMcpFields(): void {
    $this->drupalGet('/mcp/tools/list');
    $data = json_decode($this->getSession()->getPage()->getContent(), TRUE);

    // Find the test.example tool which has McpTool attribute.
    $test_tool = NULL;
    foreach ($data['tools'] as $tool) {
      if ($tool['name'] === 'test.example') {
        $test_tool = $tool;
        break;
      }
    }

    $this->assertNotNull($test_tool, 'Should find test.example tool');

    // Optional fields should be present when defined in McpTool attribute.
    if (isset($test_tool['title'])) {
      $this->assertEquals('Test MCP Tool', $test_tool['title']);
    }

    if (isset($test_tool['outputSchema'])) {
      $this->assertIsArray($test_tool['outputSchema']);
      $this->assertEquals('object', $test_tool['outputSchema']['type']);
    }

    if (isset($test_tool['annotations'])) {
      $this->assertIsArray($test_tool['annotations']);
    }
  }

  /**
   * Tests that response content type is application/json.
   */
  public function testResponseContentType(): void {
    $this->drupalGet('/mcp/tools/list');
    $this->assertSession()->responseHeaderEquals('Content-Type', 'application/json');
  }

  /**
   * Tests that unmarked JSON-RPC methods are not included.
   */
  public function testUnmarkedMethodsExcluded(): void {
    $this->drupalGet('/mcp/tools/list');
    $data = json_decode($this->getSession()->getPage()->getContent(), TRUE);

    // test.unmarked exists in test module but has no McpTool attribute.
    $tool_names = array_column($data['tools'], 'name');
    $this->assertNotContains('test.unmarked', $tool_names, 'Methods without McpTool attribute should not be discovered');
  }

}
