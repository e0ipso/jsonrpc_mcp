<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonrpc_mcp\Kernel\Controller;

use Drupal\Core\Cache\Cache;
use Drupal\jsonrpc_mcp\Controller\McpToolsController;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests cache metadata attachment to MCP controller responses.
 *
 * @group jsonrpc_mcp
 * @coversDefaultClass \Drupal\jsonrpc_mcp\Controller\McpToolsController
 */
class McpToolsCacheMetadataTest extends KernelTestBase {

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
    'serialization',
    'jsonrpc_mcp',
    'jsonrpc_mcp_examples',
  ];

  /**
   * The controller under test.
   *
   * @var \Drupal\jsonrpc_mcp\Controller\McpToolsController
   */
  protected McpToolsController $controller;

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

    // Set current user to admin to bypass permission checks.
    $admin = $this->createUser([], NULL, TRUE);
    $this->setCurrentUser($admin);

    // Instantiate controller using its create() method.
    $this->controller = McpToolsController::create($this->container);
  }

  /**
   * Tests cache metadata on list endpoint response.
   *
   * @covers ::list
   */
  public function testListEndpointCacheMetadata(): void {
    $request = Request::create('/mcp/tools/list', 'GET');

    $response = $this->controller->list($request);
    $cache_metadata = $response->getCacheableMetadata();

    // Assert cache tags.
    $cache_tags = $cache_metadata->getCacheTags();
    $this->assertContains('jsonrpc_mcp:discovery', $cache_tags, 'Response should have jsonrpc_mcp:discovery cache tag');
    $this->assertContains('user.permissions', $cache_tags, 'Response should have user.permissions cache tag');

    // Assert cache contexts.
    $cache_contexts = $cache_metadata->getCacheContexts();
    $this->assertContains('user', $cache_contexts, 'Response should have user cache context');
    $this->assertContains('url.query_args:cursor', $cache_contexts, 'Response should have url.query_args:cursor cache context');

    // Assert permanent max-age.
    $this->assertEquals(Cache::PERMANENT, $cache_metadata->getCacheMaxAge(), 'Response should have permanent cache max-age');
  }

  /**
   * Tests cache metadata on list endpoint with cursor parameter.
   *
   * @covers ::list
   */
  public function testListEndpointWithCursorCacheMetadata(): void {
    // Test with cursor parameter to verify cache context works.
    $request = Request::create('/mcp/tools/list', 'GET', ['cursor' => base64_encode('50')]);

    $response = $this->controller->list($request);
    $cache_metadata = $response->getCacheableMetadata();

    // Assert cache contexts include cursor.
    $cache_contexts = $cache_metadata->getCacheContexts();
    $this->assertContains('url.query_args:cursor', $cache_contexts, 'Response with cursor should have url.query_args:cursor cache context');

    // Verify max-age is still permanent for paginated results.
    $this->assertEquals(Cache::PERMANENT, $cache_metadata->getCacheMaxAge(), 'Paginated response should still have permanent cache max-age');
  }

  /**
   * Tests cache metadata on describe endpoint response.
   *
   * @covers ::describe
   */
  public function testDescribeEndpointCacheMetadata(): void {
    $request = Request::create('/mcp/tools/describe', 'GET', ['name' => 'examples.contentTypes.list']);

    $response = $this->controller->describe($request);
    $cache_metadata = $response->getCacheableMetadata();

    // Assert cache tags.
    $cache_tags = $cache_metadata->getCacheTags();
    $this->assertContains('jsonrpc_mcp:discovery', $cache_tags, 'Describe response should have jsonrpc_mcp:discovery cache tag');
    $this->assertContains('user.permissions', $cache_tags, 'Describe response should have user.permissions cache tag');

    // Assert cache contexts include query arg.
    $cache_contexts = $cache_metadata->getCacheContexts();
    $this->assertContains('user', $cache_contexts, 'Describe response should have user cache context');
    $this->assertContains('url.query_args:name', $cache_contexts, 'Describe response should have url.query_args:name cache context');

    // Assert permanent max-age.
    $this->assertEquals(Cache::PERMANENT, $cache_metadata->getCacheMaxAge(), 'Describe response should have permanent cache max-age');
  }

  /**
   * Tests cache metadata varies by user permissions.
   *
   * @covers ::list
   */
  public function testCacheMetadataVariesByUserPermissions(): void {
    // Test with admin user (should see all tools).
    $admin = $this->createUser([], NULL, TRUE);
    $this->setCurrentUser($admin);

    $request = Request::create('/mcp/tools/list', 'GET');
    $response = $this->controller->list($request);
    $cache_metadata = $response->getCacheableMetadata();

    // Verify user cache context is present.
    $this->assertContains('user', $cache_metadata->getCacheContexts(), 'Cache should vary by user');
    $this->assertContains('user.permissions', $cache_metadata->getCacheTags(), 'Cache should be tagged with user.permissions');

    // Test with limited permission user.
    $limited_user = $this->createUser(['access content']);
    $this->setCurrentUser($limited_user);

    $request = Request::create('/mcp/tools/list', 'GET');
    $response = $this->controller->list($request);
    $cache_metadata = $response->getCacheableMetadata();

    // Same cache metadata structure should be present.
    $this->assertContains('user', $cache_metadata->getCacheContexts(), 'Cache should vary by user for limited permission user');
    $this->assertContains('user.permissions', $cache_metadata->getCacheTags(), 'Cache should be tagged with user.permissions for limited permission user');
  }

}
