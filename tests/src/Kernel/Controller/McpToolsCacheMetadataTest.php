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
    $this->installConfig(['system', 'user']);

    // Set current user to admin to bypass permission checks.
    $admin = $this->createUser([], NULL, TRUE);
    $this->setCurrentUser($admin);

    // Get the controller from container.
    $this->controller = $this->container->get('jsonrpc_mcp.tools_controller');
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
   * Tests cache metadata on describe endpoint error responses.
   *
   * @covers ::describe
   */
  public function testDescribeEndpointErrorCacheMetadata(): void {
    // Test missing name parameter.
    $request = Request::create('/mcp/tools/describe', 'GET');

    $response = $this->controller->describe($request);
    $cache_metadata = $response->getCacheableMetadata();

    // Error responses should not be cached.
    $this->assertEquals(0, $cache_metadata->getCacheMaxAge(), 'Error response should have max-age of 0');

    // Test non-existent tool.
    $request = Request::create('/mcp/tools/describe', 'GET', ['name' => 'nonexistent.tool']);

    $response = $this->controller->describe($request);
    $cache_metadata = $response->getCacheableMetadata();

    // Error responses should not be cached.
    $this->assertEquals(0, $cache_metadata->getCacheMaxAge(), 'Tool not found response should have max-age of 0');
  }

  /**
   * Tests invoke endpoint is not cached.
   *
   * @covers ::invoke
   */
  public function testInvokeEndpointNotCached(): void {
    $request_body = json_encode([
      'name' => 'examples.contentTypes.list',
      'arguments' => [],
    ]);
    $request = Request::create('/mcp/tools/invoke', 'POST', [], [], [], [], $request_body);

    $response = $this->controller->invoke($request);
    $cache_metadata = $response->getCacheableMetadata();

    // Assert max-age is 0 (no caching).
    $this->assertEquals(0, $cache_metadata->getCacheMaxAge(), 'Invoke response should have max-age of 0 (not cached)');
  }

  /**
   * Tests invoke endpoint error responses are not cached.
   *
   * @covers ::invoke
   */
  public function testInvokeEndpointErrorsNotCached(): void {
    // Test invalid JSON.
    $request = Request::create('/mcp/tools/invoke', 'POST', [], [], [], [], 'invalid json');

    $response = $this->controller->invoke($request);
    $cache_metadata = $response->getCacheableMetadata();

    $this->assertEquals(0, $cache_metadata->getCacheMaxAge(), 'Invalid JSON error response should not be cached');

    // Test missing name parameter.
    $request_body = json_encode(['arguments' => []]);
    $request = Request::create('/mcp/tools/invoke', 'POST', [], [], [], [], $request_body);

    $response = $this->controller->invoke($request);
    $cache_metadata = $response->getCacheableMetadata();

    $this->assertEquals(0, $cache_metadata->getCacheMaxAge(), 'Missing name error response should not be cached');

    // Test missing arguments parameter.
    $request_body = json_encode(['name' => 'test.method']);
    $request = Request::create('/mcp/tools/invoke', 'POST', [], [], [], [], $request_body);

    $response = $this->controller->invoke($request);
    $cache_metadata = $response->getCacheableMetadata();

    $this->assertEquals(0, $cache_metadata->getCacheMaxAge(), 'Missing arguments error response should not be cached');

    // Test tool not found.
    $request_body = json_encode([
      'name' => 'nonexistent.tool',
      'arguments' => [],
    ]);
    $request = Request::create('/mcp/tools/invoke', 'POST', [], [], [], [], $request_body);

    $response = $this->controller->invoke($request);
    $cache_metadata = $response->getCacheableMetadata();

    $this->assertEquals(0, $cache_metadata->getCacheMaxAge(), 'Tool not found error response should not be cached');
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
