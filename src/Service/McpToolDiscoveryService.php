<?php

declare(strict_types=1);

namespace Drupal\jsonrpc_mcp\Service;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jsonrpc\HandlerInterface;
use Drupal\jsonrpc\MethodInterface;
use Drupal\jsonrpc_mcp\Attribute\McpTool;

/**
 * Service for discovering JSON-RPC methods marked as MCP tools.
 *
 * This service scans all registered JSON-RPC methods and identifies those
 * marked with the #[McpTool] attribute, applying access control filtering
 * based on the current user's permissions.
 */
class McpToolDiscoveryService {

  /**
   * Constructs a new McpToolDiscoveryService.
   *
   * @param \Drupal\jsonrpc\HandlerInterface $handler
   *   The JSON-RPC handler service.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user service.
   */
  public function __construct(
    protected HandlerInterface $handler,
    protected AccountProxyInterface $currentUser,
  ) {}

  /**
   * Discovers all accessible JSON-RPC methods marked as MCP tools.
   *
   * This method retrieves all JSON-RPC methods from the handler, filters them
   * to include only those with the #[McpTool] attribute, and applies access
   * control checks to ensure the current user has permission to execute them.
   *
   * @return \Drupal\jsonrpc\MethodInterface[]
   *   Array of method interfaces with McpTool attribute, keyed by method ID.
   *   Only includes methods the current user has permission to execute.
   */
  public function discoverTools(): array {
    $all_methods = $this->handler->supportedMethods();
    $mcp_tools = [];

    foreach ($all_methods as $method_id => $method) {
      // Check if method has McpTool attribute.
      if (!$this->hasMcpToolAttribute($method)) {
        continue;
      }

      // Check access permissions.
      if (!$method->access('execute', $this->currentUser, FALSE)) {
        continue;
      }

      $mcp_tools[$method_id] = $method;
    }

    return $mcp_tools;
  }

  /**
   * Checks if a method has the McpTool attribute.
   *
   * Uses PHP's Reflection API to inspect the plugin class and detect
   * the presence of the #[McpTool] attribute.
   *
   * @param \Drupal\jsonrpc\MethodInterface $method
   *   The JSON-RPC method to check.
   *
   * @return bool
   *   TRUE if the method has the McpTool attribute, FALSE otherwise.
   */
  protected function hasMcpToolAttribute(MethodInterface $method): bool {
    // Use reflection to check for McpTool attribute on the plugin class.
    $class = $method->getClass();

    if (!class_exists($class)) {
      return FALSE;
    }

    $reflection = new \ReflectionClass($class);
    $attributes = $reflection->getAttributes(McpTool::class);
    return !empty($attributes);
  }

  /**
   * Invalidates the MCP tool discovery cache.
   *
   * This method clears all cached discovery responses by invalidating
   * the custom cache tag. Call this when plugin definitions change or
   * when manual cache clearing is needed.
   */
  public function invalidateDiscoveryCache(): void {
    Cache::invalidateTags(['jsonrpc_mcp:discovery']);
  }

}
