<?php

declare(strict_types=1);

namespace Drupal\jsonrpc_mcp\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\jsonrpc_mcp\Service\McpToolDiscoveryService;
use Symfony\Component\Routing\Route;

/**
 * Access check for MCP tool routes.
 */
class McpToolAccessCheck implements AccessInterface {

  /**
   * Constructs a new McpToolAccessCheck.
   *
   * @param \Drupal\jsonrpc_mcp\Service\McpToolDiscoveryService $toolDiscovery
   *   The tool discovery service.
   */
  public function __construct(
    protected McpToolDiscoveryService $toolDiscovery,
  ) {}

  /**
   * Checks access to MCP tool routes.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check access for.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to check access for.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, AccountInterface $account): AccessResultInterface {
    $tool_name = $route->getDefault('tool_name');

    // Load tool metadata.
    $tools = $this->toolDiscovery->discoverTools();
    if (!isset($tools[$tool_name])) {
      return AccessResult::forbidden('Tool not found')
        ->addCacheTags(['jsonrpc_mcp:discovery']);
    }

    $method = $tools[$tool_name];
    $mcp_data = $this->extractMcpToolData($method);
    $auth_level = $mcp_data['annotations']['auth']['level'] ?? NULL;

    // If authentication required, deny anonymous access.
    if ($auth_level === 'required' && $account->isAnonymous()) {
      return AccessResult::forbidden('Authentication required')
        ->addCacheContexts(['user.roles:anonymous'])
        ->addCacheTags(['jsonrpc_mcp:discovery']);
    }

    // Allow access - detailed checks happen in controller.
    return AccessResult::allowed()
      ->addCacheContexts(['user'])
      ->addCacheTags(['jsonrpc_mcp:discovery']);
  }

  /**
   * Extracts McpTool attribute data via reflection.
   *
   * @param \Drupal\jsonrpc\MethodInterface $method
   *   The JSON-RPC method.
   *
   * @return array
   *   Associative array with 'title' and 'annotations' keys.
   */
  protected function extractMcpToolData($method): array {
    // Ensure the class is defined.
    $class = $method->getClass();
    if (!$class) {
      return ['title' => NULL, 'annotations' => NULL];
    }

    // Use reflection to read the McpTool attribute.
    $reflection = new \ReflectionClass($class);
    $attributes = $reflection->getAttributes(\Drupal\jsonrpc_mcp\Attribute\McpTool::class);

    if (empty($attributes)) {
      return ['title' => NULL, 'annotations' => NULL];
    }

    // Get the first McpTool attribute instance.
    $mcp_tool = $attributes[0]->newInstance();

    return [
      'title' => $mcp_tool->title,
      'annotations' => $mcp_tool->annotations,
    ];
  }

}
