<?php

declare(strict_types=1);

namespace Drupal\jsonrpc_mcp\Routing;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\jsonrpc_mcp\Attribute\McpTool;
use Drupal\jsonrpc_mcp\Service\McpToolDiscoveryService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Dynamically generates routes for MCP tools.
 */
final class McpToolRoutes implements ContainerInjectionInterface {

  /**
   * Constructs a new McpToolRoutes.
   *
   * @param \Drupal\jsonrpc_mcp\Service\McpToolDiscoveryService $toolDiscovery
   *   The tool discovery service.
   */
  public function __construct(
    protected McpToolDiscoveryService $toolDiscovery,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jsonrpc_mcp.tool_discovery'),
    );
  }

  /**
   * Returns dynamic routes for all discovered MCP tools.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   The route collection.
   */
  public function routes(): RouteCollection {
    $collection = new RouteCollection();
    $tools = $this->toolDiscovery->discoverTools();

    array_walk($tools, function ($method, $tool_name) use ($collection) {
      $route_data = $this->createRouteForTool($tool_name, $method);
      $collection->add($route_data['name'], $route_data['route']);
    });

    return $collection;
  }

  /**
   * Creates a route for a single MCP tool.
   *
   * @param string $tool_name
   *   The tool name.
   * @param \Drupal\jsonrpc\MethodInterface $method
   *   The JSON-RPC method.
   *
   * @return array
   *   Array with 'name' and 'route' keys.
   */
  protected function createRouteForTool(string $tool_name, $method): array {
    $route_name = 'jsonrpc_mcp.tool.' . str_replace('.', '_', $tool_name);
    $mcp_data = $this->extractMcpToolData($method);

    // Store auth metadata in route defaults for controller access.
    $auth_level = $mcp_data['annotations']['auth']['level'] ?? NULL;
    $auth_scopes = $mcp_data['annotations']['auth']['scopes'] ?? [];

    // Invocation endpoints should not be cached because they execute
    // state-changing operations. Routes are always accessible - the controller
    // handles all authentication/authorization.
    $route = new Route(
      '/mcp/tools/' . $tool_name,
      [
        '_controller' => '\Drupal\jsonrpc_mcp\Controller\McpToolInvokeController::invoke',
        '_title' => 'Invoke ' . $tool_name,
        'tool_name' => $tool_name,
        '_mcp_auth_level' => $auth_level,
        '_mcp_auth_scopes' => $auth_scopes,
        '_mcp_title' => $mcp_data['title'],
      ],
      [
        '_access' => 'TRUE',
      ],
      [
        'no_cache' => TRUE,
        'methods' => ['GET', 'POST'],
      ]
    );

    return [
      'name' => $route_name,
      'route' => $route,
    ];
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
    $class = $method->getClass();

    if (!$class) {
      return ['title' => NULL, 'annotations' => NULL];
    }

    $reflection = new \ReflectionClass($class);
    $attributes = $reflection->getAttributes(McpTool::class);

    if (empty($attributes)) {
      return ['title' => NULL, 'annotations' => NULL];
    }

    $mcp_tool = $attributes[0]->newInstance();

    return [
      'title' => $mcp_tool->title,
      'annotations' => $mcp_tool->annotations,
    ];
  }

}
