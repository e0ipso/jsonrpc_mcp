---
id: 4
group: 'example-methods'
dependencies: [1]
status: 'pending'
created: '2025-10-01'
skills: ['drupal-backend', 'entity-query']
---

# Implement ListArticles JSON-RPC Method

## Objective

Create a complex example method that lists article nodes with pagination, demonstrating entity queries, pagination patterns, and access control.

## Skills Required

- `drupal-backend`: Entity query API, pagination, node access system
- `php`: Array manipulation, pagination logic

## Acceptance Criteria

- [ ] `ListArticles.php` plugin class created
- [ ] Method accepts optional `page` parameter using PaginationParameterFactory
- [ ] Method queries article nodes using EntityQuery
- [ ] Method returns paginated results with nid, title, and created date
- [ ] Method respects entity access control
- [ ] Method includes `#[JsonRpcMethod]` attribute with pagination parameter
- [ ] Method includes commented `#[McpTool]` placeholder

## Technical Requirements

- Method ID: `examples.articles.list`
- Access permission: `["access content"]`
- Parameter: `page` (optional, uses PaginationParameterFactory)
- Output: Array of article objects with basic metadata
- Service: EntityTypeManager for entity queries

## Input Dependencies

- Task 1: Module structure and namespace

## Output Artifacts

- `modules/jsonrpc_mcp_examples/src/Plugin/jsonrpc/Method/ListArticles.php`

<details>
<summary>Implementation Notes</summary>

### Class Structure

```php
<?php

declare(strict_types=1);

namespace Drupal\jsonrpc_mcp_examples\Plugin\jsonrpc\Method;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\jsonrpc\Attribute\JsonRpcMethod;
use Drupal\jsonrpc\Attribute\JsonRpcParameterDefinition;
use Drupal\jsonrpc\JsonRpcObject\ParameterBag;
use Drupal\jsonrpc\ParameterFactory\PaginationParameterFactory;
use Drupal\jsonrpc\Plugin\JsonRpcMethodBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Lists article nodes with pagination support.
 *
 * This example demonstrates:
 * - EntityQuery usage for content retrieval
 * - Pagination using PaginationParameterFactory
 * - Entity access checking
 * - Structured output with multiple fields
 */
#[JsonRpcMethod(
  id: "examples.articles.list",
  usage: new TranslatableMarkup("Lists article nodes with optional pagination"),
  access: ["access content"],
  params: [
    'page' => new JsonRpcParameterDefinition(
      'page',
      NULL,
      PaginationParameterFactory::class,
      new TranslatableMarkup("Pagination parameters (offset and limit)"),
      FALSE
    ),
  ]
)]
// TODO: Replace with actual #[McpTool] attribute once implemented
// #[McpTool(
//   title: "List Articles",
//   annotations: ['category' => 'content', 'supports_pagination' => true]
// )]
class ListArticles extends JsonRpcMethodBase {

  public function __construct(
    array $configuration,
    string $plugin_id,
    $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  public function execute(ParameterBag $params): array {
    $pagination = $params->get('page');

    // Build entity query
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'article')
      ->condition('status', 1)
      ->accessCheck(TRUE)
      ->sort('created', 'DESC');

    // Apply pagination if provided
    if ($pagination) {
      $query->range($pagination['offset'], $pagination['limit']);
    }

    $nids = $query->execute();

    if (empty($nids)) {
      return [];
    }

    // Load nodes
    $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);

    $result = [];
    foreach ($nodes as $node) {
      // Double-check access (query accessCheck is applied, but be explicit)
      if ($node->access('view')) {
        $result[] = [
          'nid' => (int) $node->id(),
          'title' => $node->getTitle(),
          'created' => (int) $node->getCreatedTime(),
        ];
      }
    }

    return $result;
  }

  public static function outputSchema(): array {
    return [
      'type' => 'array',
      'items' => [
        'type' => 'object',
        'properties' => [
          'nid' => [
            'type' => 'integer',
            'description' => 'Node ID',
          ],
          'title' => [
            'type' => 'string',
            'description' => 'Article title',
          ],
          'created' => [
            'type' => 'integer',
            'description' => 'Creation timestamp',
          ],
        ],
        'required' => ['nid', 'title', 'created'],
      ],
    ];
  }

}
```

### Key Implementation Points

1. **Pagination Parameter**: Uses PaginationParameterFactory (optional parameter)
2. **Entity Query**: Filters by type='article', status=1, applies access check
3. **Sorting**: Orders by created date descending (newest first)
4. **Access Control**:
   - Query-level: `->accessCheck(TRUE)`
   - Entity-level: `$node->access('view')` for extra safety
5. **Structured Output**: Returns array with nid, title, created timestamp
6. **Empty Result Handling**: Returns empty array if no results

### Pagination Usage

Without pagination (returns all articles):

```bash
drush jsonrpc:request examples.articles.list '{}'
```

With pagination (offset 0, limit 10):

```bash
drush jsonrpc:request examples.articles.list '{"page": {"offset": 0, "limit": 10}}'
```

### Expected Output Example

```json
[
  {
    "nid": 42,
    "title": "Latest Article",
    "created": 1696118400
  },
  {
    "nid": 41,
    "title": "Previous Article",
    "created": 1696032000
  }
]
```

</details>
