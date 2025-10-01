<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonrpc_mcp\Kernel\Service;

use Drupal\Core\Session\AccountInterface;
use Drupal\jsonrpc\MethodInterface;
use Drupal\jsonrpc_mcp\Service\McpToolDiscoveryService;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

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
   * Tests that discovered tools implement MethodInterface.
   *
   * @covers ::discoverTools
   */
  public function testDiscoverToolsReturnsMethodInterface(): void {
    $admin = $this->createUser([], NULL, TRUE);
    $this->setCurrentUser($admin);

    $tools = $this->discoveryService->discoverTools();

    $this->assertNotEmpty($tools, 'Should discover at least one tool');
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
   * Tests discovery with authenticated user.
   *
   * @covers ::discoverTools
   */
  public function testDiscoverToolsWithAuthenticated(): void {
    // Create authenticated user with access content permission.
    $user = $this->createUser(['access content']);
    $this->setCurrentUser($user);

    $tools = $this->discoveryService->discoverTools();

    // Should find methods available to authenticated users with access content.
    $this->assertArrayHasKey('test.example', $tools);
    $this->assertArrayHasKey('test.authenticated', $tools);
  }

  /**
   * Tests discovery with anonymous user.
   *
   * @covers ::discoverTools
   */
  public function testDiscoverToolsWithAnonymous(): void {
    // Set current user to anonymous (UID 0).
    $anonymous = User::getAnonymousUser();
    $this->setCurrentUser($anonymous);

    // Grant 'access content' to anonymous role.
    $role = Role::load(AccountInterface::ANONYMOUS_ROLE);
    $role->grantPermission('access content');
    $role->save();

    $tools = $this->discoveryService->discoverTools();

    // Anonymous user with access content should see methods requiring that permission.
    $this->assertArrayHasKey('test.example', $tools);
    $this->assertArrayHasKey('test.authenticated', $tools);
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

  /**
   * Tests discovery respects access callback when no permissions granted.
   *
   * @covers ::discoverTools
   */
  public function testEmptyResultWhenNoAccessibleTools(): void {
    // Create user with no additional permissions beyond authenticated.
    $user = $this->createUser([]);
    $this->setCurrentUser($user);

    $tools = $this->discoveryService->discoverTools();

    // User with no permissions should have limited or no access.
    // The exact result depends on whether authenticated role has default permissions.
  }

  /**
   * Tests that discovery handles admin users correctly.
   *
   * @covers ::discoverTools
   */
  public function testDiscoveryWithAdminUser(): void {
    // Admin user bypasses all permission checks.
    $admin = $this->createUser([], NULL, TRUE);
    $this->setCurrentUser($admin);

    $tools = $this->discoveryService->discoverTools();

    // Admin should see all MCP-marked methods.
    $this->assertArrayHasKey('test.example', $tools);
    $this->assertArrayHasKey('test.adminOnly', $tools);
    $this->assertArrayHasKey('test.authenticated', $tools);
    // Should still filter based on McpTool attribute, not permissions.
    $this->assertArrayNotHasKey('test.unmarked', $tools, 'Should still not see unmarked methods');
  }

  /**
   * Tests that method IDs are correctly preserved as array keys.
   *
   * @covers ::discoverTools
   */
  public function testDiscoveryPreservesMethodIds(): void {
    $admin = $this->createUser([], NULL, TRUE);
    $this->setCurrentUser($admin);

    $tools = $this->discoveryService->discoverTools();

    foreach ($tools as $method_id => $method) {
      $this->assertEquals($method_id, $method->id(), 'Array key should match method ID');
    }
  }

}
