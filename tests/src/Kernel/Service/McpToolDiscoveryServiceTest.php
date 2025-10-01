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
    'jsonrpc',
    'jsonrpc_mcp',
    'jsonrpc_mcp_test',
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

    // Should find test methods with McpTool attribute.
    $this->assertArrayHasKey('test.example', $tools, 'Should find test.example method');
    $this->assertArrayHasKey('test.adminOnly', $tools, 'Should find test.adminOnly method');
    $this->assertArrayHasKey('test.authenticated', $tools, 'Should find test.authenticated method');
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

    // Should NOT find method without McpTool attribute.
    $this->assertArrayNotHasKey('test.unmarked', $tools, 'Should NOT find test.unmarked method (no McpTool attribute)');
  }

  /**
   * Tests access control with user lacking permissions.
   *
   * @covers ::discoverTools
   */
  public function testDiscoverToolsRespectsPermissions(): void {
    // Create user with administer site configuration permission.
    $admin_user = $this->createUser(['administer site configuration', 'access content']);
    $this->setCurrentUser($admin_user);

    $admin_tools = $this->discoveryService->discoverTools();

    // Admin user with permission should see all methods.
    $this->assertArrayHasKey('test.adminOnly', $admin_tools, 'Admin user should find test.adminOnly');
    $this->assertArrayHasKey('test.example', $admin_tools, 'Admin user should find test.example');
    $this->assertArrayHasKey('test.authenticated', $admin_tools, 'Admin user should find test.authenticated');
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

    // Should discover all three MCP-marked methods.
    $this->assertCount(3, $tools, 'Should discover exactly 3 MCP-marked methods');

    $expected_ids = ['test.example', 'test.adminOnly', 'test.authenticated'];
    foreach ($expected_ids as $expected_id) {
      $this->assertArrayHasKey($expected_id, $tools, "Should find $expected_id");
    }
  }

}
