<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonrpc_mcp\Kernel;

use Drupal\jsonrpc_mcp\Service\McpToolDiscoveryService;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Kernel tests for tool discovery with authentication metadata.
 *
 * Tests that the discovery service correctly identifies and extracts
 * authentication metadata from MCP tool plugins, including:
 * - Explicit auth metadata with scopes and level
 * - Tools without auth metadata (null)
 * - Inferred auth level from scopes
 *
 * @group jsonrpc_mcp
 * @coversDefaultClass \Drupal\jsonrpc_mcp\Service\McpToolDiscoveryService
 */
class ToolDiscoveryAuthTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   *
   * Note: We load jsonrpc_mcp manually but not through $modules to avoid
   * middleware circular dependency. We install the auth test module which
   * contains the test methods.
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'text',
    'jsonrpc',
  ];

  /**
   * The discovery service under test.
   *
   * @var \Drupal\jsonrpc_mcp\Service\McpToolDiscoveryService
   */
  protected McpToolDiscoveryService $discoveryService;

  /**
   * The normalizer service.
   *
   * @var \Drupal\jsonrpc_mcp\Normalizer\McpToolNormalizer
   */
  protected $normalizer;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Install required entity schemas and configs.
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['system', 'user', 'node', 'field']);

    // Enable jsonrpc_mcp and test module without middleware.
    // We must install the module code but not register services to avoid
    // circular dependency with http_middleware.
    $this->enableModules(['jsonrpc_mcp', 'jsonrpc_mcp_auth_test']);

    // Get the discovery service and normalizer.
    $this->discoveryService = $this->container->get('jsonrpc_mcp.tool_discovery');
    $this->normalizer = $this->container->get('jsonrpc_mcp.tool_normalizer');
  }

  /**
   * Tests discovery finds tools with authentication metadata.
   *
   * @covers ::discoverTools
   */
  public function testDiscoveryFindsToolsWithAuth(): void {
    $admin = $this->createUser([], NULL, TRUE);
    $this->setCurrentUser($admin);

    $tools = $this->discoveryService->discoverTools();

    // Should find the test method with auth.
    $this->assertArrayHasKey('test.methodWithAuth', $tools);

    // Normalize the tool to get array representation.
    $tool_definition = $this->normalizer->normalize($tools['test.methodWithAuth']);

    // Verify auth metadata is present in annotations.
    $this->assertArrayHasKey('annotations', $tool_definition);
    $this->assertArrayHasKey('auth', $tool_definition['annotations']);

    $auth = $tool_definition['annotations']['auth'];
    $this->assertEquals(['content:read', 'content:write'], $auth['scopes']);
    $this->assertEquals('required', $auth['level']);
    $this->assertEquals('Requires content read and write access', $auth['description']);
  }

  /**
   * Tests discovery handles tools without authentication metadata.
   *
   * @covers ::discoverTools
   */
  public function testDiscoveryHandlesToolsWithoutAuth(): void {
    $admin = $this->createUser([], NULL, TRUE);
    $this->setCurrentUser($admin);

    $tools = $this->discoveryService->discoverTools();

    // Should find the test method without auth.
    $this->assertArrayHasKey('test.methodWithoutAuth', $tools);

    // Normalize the tool to get array representation.
    $tool_definition = $this->normalizer->normalize($tools['test.methodWithoutAuth']);

    // Should have annotations but no auth key.
    $this->assertArrayHasKey('annotations', $tool_definition);
    $this->assertArrayNotHasKey('auth', $tool_definition['annotations']);
  }

  /**
   * Tests discovery with inferred auth level.
   *
   * @covers ::discoverTools
   */
  public function testDiscoveryWithInferredAuthLevel(): void {
    $admin = $this->createUser([], NULL, TRUE);
    $this->setCurrentUser($admin);

    $tools = $this->discoveryService->discoverTools();

    // Should find the test method with inferred auth.
    $this->assertArrayHasKey('test.methodWithInferredAuth', $tools);

    // Normalize the tool to get array representation.
    $tool_definition = $this->normalizer->normalize($tools['test.methodWithInferredAuth']);

    // Verify auth metadata structure.
    $this->assertArrayHasKey('annotations', $tool_definition);
    $this->assertArrayHasKey('auth', $tool_definition['annotations']);

    $auth = $tool_definition['annotations']['auth'];
    $this->assertEquals(['user:read'], $auth['scopes']);
    $this->assertArrayNotHasKey('level', $auth, 'Level should not be explicitly set');
    $this->assertEquals('Inferred required level from scopes', $auth['description']);
  }

  /**
   * Tests that auth metadata is properly structured.
   *
   * @covers ::discoverTools
   */
  public function testAuthMetadataStructure(): void {
    $admin = $this->createUser([], NULL, TRUE);
    $this->setCurrentUser($admin);

    $tools = $this->discoveryService->discoverTools();

    foreach ($tools as $tool_id => $method) {
      // Normalize the tool to get array representation.
      $definition = $this->normalizer->normalize($method);

      if (isset($definition['annotations']['auth'])) {
        $auth = $definition['annotations']['auth'];

        // Auth metadata must have scopes array.
        $this->assertArrayHasKey('scopes', $auth, "Tool $tool_id auth must have scopes");
        $this->assertIsArray($auth['scopes'], "Tool $tool_id scopes must be array");

        // Auth metadata should have description.
        if (isset($auth['description'])) {
          $this->assertIsString($auth['description'], "Tool $tool_id auth description must be string");
        }

        // If level is present, must be valid.
        if (isset($auth['level'])) {
          $this->assertContains(
            $auth['level'],
            ['none', 'optional', 'required'],
            "Tool $tool_id auth level must be valid"
          );
        }
      }
    }
  }

  /**
   * Tests permissions still apply with auth metadata.
   *
   * @covers ::discoverTools
   */
  public function testAuthMetadataRespectsPermissions(): void {
    // User without 'access content' permission.
    $user = $this->createUser([]);
    $this->setCurrentUser($user);

    $tools = $this->discoveryService->discoverTools();

    // Should not see any test methods because they require 'access content'.
    $this->assertArrayNotHasKey('test.methodWithAuth', $tools);
    $this->assertArrayNotHasKey('test.methodWithoutAuth', $tools);
    $this->assertArrayNotHasKey('test.methodWithInferredAuth', $tools);

    // Admin should see all methods.
    $admin = $this->createUser([], NULL, TRUE);
    $this->setCurrentUser($admin);

    $admin_tools = $this->discoveryService->discoverTools();
    $this->assertArrayHasKey('test.methodWithAuth', $admin_tools);
    $this->assertArrayHasKey('test.methodWithoutAuth', $admin_tools);
    $this->assertArrayHasKey('test.methodWithInferredAuth', $admin_tools);
  }

}
