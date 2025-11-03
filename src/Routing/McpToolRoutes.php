<?php

declare(strict_types=1);

namespace Drupal\jsonrpc_mcp\Routing;

use Drupal\jsonrpc_mcp\Service\McpToolDiscoveryService;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Dynamically generates routes for MCP tools.
 */
class McpToolRoutes {

  /**
   * Returns dynamic routes for all discovered MCP tools.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   The route collection.
   */
  public static function routes(): RouteCollection {
    $collection = new RouteCollection();
    $toolDiscovery = \Drupal::service('jsonrpc_mcp.tool_discovery');
    $tools = $toolDiscovery->discoverTools();

    foreach ($tools as $tool_name => $method) {
      // Convert dots to underscores for route name.
      $route_name = 'jsonrpc_mcp.tool.' . str_replace('.', '_', $tool_name);

      // Determine if authentication is required.
      $mcp_data = static::extractMcpToolData($method);
      $auth_level = $mcp_data['annotations']['auth']['level'] ?? NULL;
      $requires_auth = $auth_level === 'required';

      $route = new Route(
        '/mcp/tools/' . $tool_name,
        [
          '_controller' => '\Drupal\jsonrpc_mcp\Controller\McpToolInvokeController::invoke',
          '_title' => 'Invoke ' . $tool_name,
          'tool_name' => $tool_name,
        ],
        [
          '_method' => 'GET|POST',
        ],
        [
          'no_cache' => TRUE,
        ]
      );

      // Add custom access check if authentication required.
      if ($requires_auth) {
        $route->setRequirement('_custom_access', '\Drupal\jsonrpc_mcp\Access\McpToolAccessCheck::access');
      }
      else {
        $route->setRequirement('_access', 'TRUE');
      }

      $collection->add($route_name, $route);
    }

    return $collection;
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
  protected static function extractMcpToolData($method): array {
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
