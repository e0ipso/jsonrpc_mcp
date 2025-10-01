<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonrpc_mcp\Kernel\Service;

use Drupal\jsonrpc_mcp\Service\McpToolDiscoveryService;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Kernel tests for McpToolDiscoveryService.
 *
 * @group jsonrpc_mcp
 * @coversDefaultClass \Drupal\jsonrpc_mcp\Service\McpToolDiscoveryService
 */
class McpToolDiscoveryServiceTest extends KernelTestBase {

  use UserCreationTrait;

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
   * The discovery service under test.
   *
   * @var \Drupal\jsonrpc_mcp\Service\McpToolDiscoveryService
   */
  protected McpToolDiscoveryService $discoveryService;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Install required entity schemas and configs.
    $this->installEntitySchema('user');
    $this->installConfig(['system', 'user']);

    // Get the discovery service.
    $this->discoveryService = $this->container->get('jsonrpc_mcp.tool_discovery');
  }

  /**
   * Tests that methods with McpTool attribute are discovered.
   *
   * @covers ::discoverTools
   * @covers ::hasMcpToolAttribute
   */
  public function testDiscoverToolsFindsMarkedMethods(): void {
    // Set current user to admin to bypass permissions.
    $admin = $this->createUser([], NULL, TRUE);
    $this->setCurrentUser($admin);

    $tools = $this->discoveryService->discoverTools();

    // Should find example methods with McpTool attribute.
    $this->assertArrayHasKey('examples.contentTypes.list', $tools, 'Should find examples.contentTypes.list method');
    $this->assertArrayHasKey('examples.articles.list', $tools, 'Should find examples.articles.list method');
    $this->assertArrayHasKey('examples.article.toMarkdown', $tools, 'Should find examples.article.toMarkdown method');
  }

  /**
   * Tests that methods without McpTool attribute are excluded.
   *
   * @covers ::discoverTools
   * @covers ::hasMcpToolAttribute
   */
  public function testDiscoverToolsExcludesUnmarkedMethods(): void {
    // Set current user to admin to bypass permissions.
    $admin = $this->createUser([], NULL, TRUE);
    $this->setCurrentUser($admin);

    $tools = $this->discoveryService->discoverTools();

    // Verify that only methods with McpTool attribute are discovered.
    // All discovered tools should have the expected structure.
    foreach ($tools as $tool_id => $definition) {
      $this->assertStringStartsWith('examples.', $tool_id, 'Only example methods with McpTool should be discovered');
    }

    // Verify we found exactly the expected number of methods.
    $this->assertCount(3, $tools, 'Should discover exactly 3 MCP-marked example methods');
  }

  /**
   * Tests access control with user lacking permissions.
   *
   * @covers ::discoverTools
   */
  public function testDiscoverToolsRespectsPermissions(): void {
    // Create user with only 'access content' permission.
    $user = $this->createUser(['access content']);
    $this->setCurrentUser($user);

    $tools = $this->discoveryService->discoverTools();

    // User with 'access content' should see all example methods since they
    // all require 'access content' permission.
    $this->assertArrayHasKey('examples.contentTypes.list', $tools);
    $this->assertArrayHasKey('examples.articles.list', $tools);
    $this->assertArrayHasKey('examples.article.toMarkdown', $tools);

    // Test with user having no permissions.
    $no_perm_user = $this->createUser([]);
    $this->setCurrentUser($no_perm_user);

    $no_perm_tools = $this->discoveryService->discoverTools();

    // User without 'access content' should not see any methods.
    $this->assertArrayNotHasKey('examples.contentTypes.list', $no_perm_tools);
    $this->assertArrayNotHasKey('examples.articles.list', $no_perm_tools);
    $this->assertArrayNotHasKey('examples.article.toMarkdown', $no_perm_tools);
  }

  /**
   * Tests discovery with multiple MCP-enabled plugins.
   *
   * @covers ::discoverTools
   */
  public function testDiscoveryWithMultiplePlugins(): void {
    $admin = $this->createUser([], NULL, TRUE);
    $this->setCurrentUser($admin);

    $tools = $this->discoveryService->discoverTools();

    // Should discover all three MCP-marked example methods.
    $this->assertCount(3, $tools, 'Should discover exactly 3 MCP-marked methods');

    $expected_ids = [
      'examples.contentTypes.list',
      'examples.articles.list',
      'examples.article.toMarkdown',
    ];
    foreach ($expected_ids as $expected_id) {
      $this->assertArrayHasKey($expected_id, $tools, "Should find $expected_id");
    }
  }

}
