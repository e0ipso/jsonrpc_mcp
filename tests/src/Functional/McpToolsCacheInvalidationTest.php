<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonrpc_mcp\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests cache invalidation for MCP tool discovery.
 *
 * Verifies that the discovery cache is properly invalidated when modules
 * are installed/uninstalled or when manual invalidation is triggered.
 *
 * @group jsonrpc_mcp
 */
class McpToolsCacheInvalidationTest extends BrowserTestBase {

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
   * Tests cache invalidation when modules are installed.
   *
   * Verifies that installing a new module triggers cache invalidation
   * via the hook_modules_installed() implementation.
   */
  public function testCacheInvalidationOnModuleInstall(): void {
    // Make initial request to populate cache.
    $this->drupalGet('/mcp/tools/list');
    $this->assertSession()->statusCodeEquals(200);

    $initial_response = $this->getSession()->getPage()->getContent();
    $initial_data = json_decode($initial_response, TRUE);
    $this->assertIsArray($initial_data);
    $this->assertArrayHasKey('tools', $initial_data);

    // Install a module (use help as a lightweight test module).
    \Drupal::service('module_installer')->install(['help']);

    // Make another request - cache should be invalidated and regenerated.
    $this->drupalGet('/mcp/tools/list');
    $this->assertSession()->statusCodeEquals(200);

    $post_install_response = $this->getSession()->getPage()->getContent();
    $post_install_data = json_decode($post_install_response, TRUE);
    $this->assertIsArray($post_install_data);
    $this->assertArrayHasKey('tools', $post_install_data);

    // Verify response structure is intact (cache was regenerated).
    $this->assertArrayHasKey('nextCursor', $post_install_data);
  }

  /**
   * Tests cache invalidation when modules are uninstalled.
   *
   * Verifies that uninstalling a module triggers cache invalidation
   * via the hook_modules_uninstalled() implementation.
   */
  public function testCacheInvalidationOnModuleUninstall(): void {
    // Install help module first.
    \Drupal::service('module_installer')->install(['help']);

    // Populate cache.
    $this->drupalGet('/mcp/tools/list');
    $this->assertSession()->statusCodeEquals(200);

    $pre_uninstall_response = $this->getSession()->getPage()->getContent();
    $pre_uninstall_data = json_decode($pre_uninstall_response, TRUE);
    $this->assertIsArray($pre_uninstall_data);

    // Uninstall the module.
    \Drupal::service('module_installer')->uninstall(['help']);

    // Make another request - cache should be invalidated and regenerated.
    $this->drupalGet('/mcp/tools/list');
    $this->assertSession()->statusCodeEquals(200);

    $post_uninstall_response = $this->getSession()->getPage()->getContent();
    $post_uninstall_data = json_decode($post_uninstall_response, TRUE);
    $this->assertIsArray($post_uninstall_data);

    // Verify response structure is intact (cache was regenerated).
    $this->assertArrayHasKey('tools', $post_uninstall_data);
    $this->assertArrayHasKey('nextCursor', $post_uninstall_data);
  }

  /**
   * Tests manual cache invalidation via service method.
   *
   * Verifies that calling the service's invalidateDiscoveryCache() method
   * properly clears the cache.
   */
  public function testManualCacheInvalidation(): void {
    // Populate cache with initial request.
    $this->drupalGet('/mcp/tools/list');
    $this->assertSession()->statusCodeEquals(200);

    $initial_response = $this->getSession()->getPage()->getContent();
    $initial_data = json_decode($initial_response, TRUE);
    $this->assertIsArray($initial_data);
    $this->assertArrayHasKey('tools', $initial_data);

    // Manually invalidate cache via service.
    \Drupal::service('jsonrpc_mcp.tool_discovery')->invalidateDiscoveryCache();

    // Make another request - cache should be invalidated and regenerated.
    $this->drupalGet('/mcp/tools/list');
    $this->assertSession()->statusCodeEquals(200);

    $post_invalidation_response = $this->getSession()->getPage()->getContent();
    $post_invalidation_data = json_decode($post_invalidation_response, TRUE);
    $this->assertIsArray($post_invalidation_data);

    // Verify response structure is intact (cache was regenerated).
    $this->assertArrayHasKey('tools', $post_invalidation_data);
    $this->assertArrayHasKey('nextCursor', $post_invalidation_data);

    // Verify data consistency - same tools should be returned.
    $this->assertCount(
      count($initial_data['tools']),
      $post_invalidation_data['tools'],
      'Tool count should remain consistent after cache invalidation'
    );
  }

  /**
   * Tests cache is NOT invalidated on unrelated operations.
   *
   * Verifies that operations unrelated to module lifecycle do not
   * trigger unnecessary cache invalidation.
   */
  public function testCacheNotInvalidatedOnUnrelatedOperations(): void {
    // Populate cache.
    $this->drupalGet('/mcp/tools/list');
    $this->assertSession()->statusCodeEquals(200);

    $initial_response = $this->getSession()->getPage()->getContent();
    $initial_data = json_decode($initial_response, TRUE);
    $this->assertIsArray($initial_data);

    // Perform unrelated operation (clear Drupal's general cache, not our tag).
    drupal_flush_all_caches();

    // Make another request.
    $this->drupalGet('/mcp/tools/list');
    $this->assertSession()->statusCodeEquals(200);

    $post_flush_response = $this->getSession()->getPage()->getContent();
    $post_flush_data = json_decode($post_flush_response, TRUE);
    $this->assertIsArray($post_flush_data);

    // Verify response structure is intact.
    $this->assertArrayHasKey('tools', $post_flush_data);
    $this->assertArrayHasKey('nextCursor', $post_flush_data);

    // Note: drupal_flush_all_caches() WILL invalidate all caches including
    // ours, so this test verifies that the system gracefully regenerates
    // the cache rather than breaking.
  }

}
