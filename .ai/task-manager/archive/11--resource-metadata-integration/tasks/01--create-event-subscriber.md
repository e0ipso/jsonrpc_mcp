---
id: 1
group: "event-integration"
dependencies: []
status: "completed"
created: "2025-11-03"
skills:
  - drupal-backend
  - symfony-events
---
# Create ResourceMetadataSubscriber Event Subscriber

## Objective

Create the event subscriber infrastructure that listens to `ResourceMetadataEvents::BUILD` and integrates MCP tool metadata into OAuth 2.0 Protected Resource Metadata responses.

## Skills Required

- **drupal-backend**: Drupal service definition, dependency injection, and module structure
- **symfony-events**: Symfony event subscriber implementation and event handling patterns

## Acceptance Criteria

- [ ] ResourceMetadataSubscriber class created in `src/EventSubscriber/`
- [ ] Class implements `EventSubscriberInterface`
- [ ] Service registered in `jsonrpc_mcp.services.yml` with `event_subscriber` tag
- [ ] `McpToolDiscoveryService` injected as dependency
- [ ] Subscribes to `ResourceMetadataEvents::BUILD` with priority 0
- [ ] Event handler method `onBuildResourceMetadata()` scaffolded
- [ ] PHPStan level 5 passes with zero errors
- [ ] Drupal coding standards pass (phpcs)

Use your internal Todo tool to track these and keep on track.

## Technical Requirements

**Event Subscription Pattern:**
```php
public static function getSubscribedEvents(): array {
  return [
    ResourceMetadataEvents::BUILD => ['onBuildResourceMetadata', 0],
  ];
}
```

**Service Definition:**
```yaml
jsonrpc_mcp.resource_metadata_subscriber:
  class: Drupal\jsonrpc_mcp\EventSubscriber\ResourceMetadataSubscriber
  arguments:
    - '@jsonrpc_mcp.tool_discovery'
  tags:
    - { name: event_subscriber }
```

**Namespace and Imports:**
- `Drupal\jsonrpc_mcp\EventSubscriber`
- `Drupal\simple_oauth_server_metadata\Event\ResourceMetadataEvent`
- `Drupal\simple_oauth_server_metadata\Event\ResourceMetadataEvents`
- `Symfony\Component\EventDispatcher\EventSubscriberInterface`

## Input Dependencies

None - this is the foundation task.

## Output Artifacts

- `src/EventSubscriber/ResourceMetadataSubscriber.php` - Event subscriber class
- Updated `jsonrpc_mcp.services.yml` - Service registration

## Implementation Notes

<details>
<summary>Detailed Implementation Guide</summary>

### File: `src/EventSubscriber/ResourceMetadataSubscriber.php`

**Class Structure:**
```php
<?php

declare(strict_types=1);

namespace Drupal\jsonrpc_mcp\EventSubscriber;

use Drupal\jsonrpc_mcp\Service\McpToolDiscoveryService;
use Drupal\simple_oauth_server_metadata\Event\ResourceMetadataEvent;
use Drupal\simple_oauth_server_metadata\Event\ResourceMetadataEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for contributing MCP tool metadata to OAuth 2.0 resource metadata.
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
    // TODO: Implementation in task 2
  }

}
```

**Key Implementation Points:**
- Use `final` class to prevent inheritance (Drupal best practice)
- Use constructor property promotion for cleaner code
- Use `protected` visibility for injected dependencies
- Priority 0 ensures subscriber runs after core metadata setup
- Event handler should be public and type-hinted

### File: `jsonrpc_mcp.services.yml` (update)

Add this service definition:

```yaml
  jsonrpc_mcp.resource_metadata_subscriber:
    class: Drupal\jsonrpc_mcp\EventSubscriber\ResourceMetadataSubscriber
    arguments:
      - '@jsonrpc_mcp.tool_discovery'
    tags:
      - { name: event_subscriber }
```

**Validation Commands:**
```bash
# Check coding standards
vendor/bin/phpcs --standard=Drupal,DrupalPractice src/EventSubscriber/

# Run PHPStan
vendor/bin/phpstan analyse src/EventSubscriber/

# Verify service registration
vendor/bin/drush ev "\Drupal::service('jsonrpc_mcp.resource_metadata_subscriber');"
```

**Error Handling:**
- Drupal's event system automatically handles missing event classes (graceful degradation)
- No special error handling needed for optional simple_oauth_server_metadata dependency
- Service will simply not be called if the event is never dispatched

</details>
