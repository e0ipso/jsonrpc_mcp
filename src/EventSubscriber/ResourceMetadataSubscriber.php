<?php

declare(strict_types=1);

namespace Drupal\jsonrpc_mcp\EventSubscriber;

use Drupal\jsonrpc_mcp\Attribute\McpTool;
use Drupal\jsonrpc_mcp\Service\McpToolDiscoveryService;
use Drupal\simple_oauth_server_metadata\Event\ResourceMetadataEvent;
use Drupal\simple_oauth_server_metadata\Event\ResourceMetadataEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for contributing MCP tool metadata to OAuth 2.0 resource.
 */
final class ResourceMetadataSubscriber implements EventSubscriberInterface {

  /**
   * Constructs a new ResourceMetadataSubscriber.
   *
   * @param \Drupal\jsonrpc_mcp\Service\McpToolDiscoveryService $toolDiscovery
   *   The MCP tool discovery service.
   */
  public function __construct(
    protected McpToolDiscoveryService $toolDiscovery,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      ResourceMetadataEvents::BUILD => ['onBuildResourceMetadata', 0],
    ];
  }

  /**
   * Responds to resource metadata build events.
   *
   * @param \Drupal\simple_oauth_server_metadata\Event\ResourceMetadataEvent $event
   *   The resource metadata event.
   */
  public function onBuildResourceMetadata(ResourceMetadataEvent $event): void {
    $tools = $this->toolDiscovery->discoverTools();

    if (empty($tools)) {
      // No tools discovered - nothing to contribute.
      return;
    }

    $all_scopes = [];
    $tool_names = [];

    foreach ($tools as $tool_name => $method) {
      $mcp_data = $this->extractMcpToolData($method);

      // Extract scopes from auth annotations.
      $scopes = $mcp_data['annotations']['auth']['scopes'] ?? [];
      if (is_array($scopes)) {
        $all_scopes = array_merge($all_scopes, $scopes);
      }

      // Collect tool names for authorization details.
      $tool_names[] = $tool_name;
    }

    // Deduplicate and sort scopes.
    $unique_scopes = array_unique($all_scopes);
    sort($unique_scopes);

    // Contribute RFC 9728 fields.
    if (!empty($unique_scopes)) {
      $event->addMetadataField('scopes_supported', $unique_scopes);
    }

    // MCP specification requires header-based Bearer token authentication.
    $event->addMetadataField('bearer_methods_supported', ['header']);

    // Add tool names as authorization details types (RFC 9396).
    sort($tool_names);
    $event->addMetadataField('authorization_details_types_supported', $tool_names);
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
