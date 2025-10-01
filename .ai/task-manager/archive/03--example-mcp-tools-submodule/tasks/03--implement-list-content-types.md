---
id: 3
group: 'example-methods'
dependencies: [1]
status: 'completed'
created: '2025-10-01'
skills: ['drupal-backend']
---

# Implement ListContentTypes JSON-RPC Method

## Objective

Create a simple example method that lists available content types, demonstrating zero-parameter methods and structured data responses.

## Skills Required

- `drupal-backend`: Drupal entity type system, plugin architecture, service injection

## Acceptance Criteria

- [ ] `ListContentTypes.php` plugin class created
- [ ] Method accepts no parameters
- [ ] Method returns array of content types with machine name and label
- [ ] Method includes `#[JsonRpcMethod]` attribute
- [ ] Method includes commented `#[McpTool]` placeholder
- [ ] Output schema properly documents return structure

## Technical Requirements

- Method ID: `examples.contentTypes.list`
- Access permission: `["access content"]`
- Parameters: None
- Output: Array of objects with `id` and `label` keys
- Service: EntityTypeBundleInfo for listing content types

## Input Dependencies

- Task 1: Module structure and namespace

## Output Artifacts

- `modules/jsonrpc_mcp_examples/src/Plugin/jsonrpc/Method/ListContentTypes.php`

<details>
<summary>Implementation Notes</summary>

### Class Structure

```php
<?php

declare(strict_types=1);

namespace Drupal\jsonrpc_mcp_examples\Plugin\jsonrpc\Method;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\jsonrpc\Attribute\JsonRpcMethod;
use Drupal\jsonrpc\JsonRpcObject\ParameterBag;
use Drupal\jsonrpc\Plugin\JsonRpcMethodBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Lists all available content types.
 *
 * This example demonstrates a simple method with no parameters
 * that returns structured data.
 */
#[JsonRpcMethod(
  id: "examples.contentTypes.list",
  usage: new TranslatableMarkup("Lists all available content types"),
  access: ["access content"]
)]
// TODO: Replace with actual #[McpTool] attribute once implemented
// #[McpTool(
//   title: "List Content Types",
//   annotations: ['category' => 'discovery']
// )]
class ListContentTypes extends JsonRpcMethodBase {

  public function __construct(
    array $configuration,
    string $plugin_id,
    $plugin_definition,
    protected EntityTypeBundleInfoInterface $bundleInfo,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.bundle.info')
    );
  }

  public function execute(ParameterBag $params): array {
    $bundles = $this->bundleInfo->getBundleInfo('node');
    $result = [];

    foreach ($bundles as $id => $info) {
      $result[] = [
        'id' => $id,
        'label' => $info['label'],
      ];
    }

    return $result;
  }

  public static function outputSchema(): array {
    return [
      'type' => 'array',
      'items' => [
        'type' => 'object',
        'properties' => [
          'id' => [
            'type' => 'string',
            'description' => 'Content type machine name',
          ],
          'label' => [
            'type' => 'string',
            'description' => 'Content type human-readable label',
          ],
        ],
        'required' => ['id', 'label'],
      ],
    ];
  }

}
```

### Key Implementation Points

1. **Zero Parameters**: Method signature has no params in attribute
2. **Service Injection**: EntityTypeBundleInfo for bundle discovery
3. **Simple Logic**: Just list bundles, no complex filtering
4. **Structured Output**: Array of objects with consistent schema
5. **Discovery Pattern**: Useful for AI assistants to discover available types

### Expected Output Example

```json
[
  { "id": "article", "label": "Article" },
  { "id": "page", "label": "Basic page" },
  { "id": "custom_type", "label": "Custom Content Type" }
]
```

### Manual Testing

```bash
drush jsonrpc:request examples.contentTypes.list '{}'
```

</details>
