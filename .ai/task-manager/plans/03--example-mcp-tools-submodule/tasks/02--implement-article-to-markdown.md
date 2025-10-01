---
id: 2
group: 'example-methods'
dependencies: [1]
status: 'pending'
created: '2025-10-01'
skills: ['drupal-backend', 'php']
---

# Implement ArticleToMarkdown JSON-RPC Method

## Objective

Create the flagship example method that retrieves an article node by ID and converts it to markdown format, demonstrating content loading, access control, and text transformation patterns.

## Skills Required

- `drupal-backend`: Drupal entity API, node access system, plugin architecture
- `php`: Regular expressions, string manipulation, attribute syntax

## Acceptance Criteria

- [ ] `ArticleToMarkdown.php` plugin class created
- [ ] Method accepts `nid` parameter (integer, minimum 1, required)
- [ ] Method validates node exists and is type 'article'
- [ ] Method checks user has 'view' access via `$node->access('view')`
- [ ] Method returns markdown formatted as `# {title}\n\n{body}`
- [ ] HTML paragraphs converted to double newlines before stripping tags
- [ ] Method includes `#[JsonRpcMethod]` attribute with proper parameters
- [ ] Method includes commented `#[McpTool]` placeholder for future implementation

## Technical Requirements

- Method ID: `examples.article.toMarkdown`
- Access permission: `["access content"]`
- Parameter: `nid` (integer, required, minimum: 1)
- Output schema: Returns string (markdown text)
- Error handling: Invalid node ID, wrong content type, access denied

## Input Dependencies

- Task 1: Module structure and namespace

## Output Artifacts

- `modules/jsonrpc_mcp_examples/src/Plugin/jsonrpc/Method/ArticleToMarkdown.php`

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
use Drupal\jsonrpc\Exception\JsonRpcException;
use Drupal\jsonrpc\JsonRpcObject\Error;
use Drupal\jsonrpc\JsonRpcObject\ParameterBag;
use Drupal\jsonrpc\Plugin\JsonRpcMethodBase;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Retrieves an article node and formats it as markdown.
 */
#[JsonRpcMethod(
  id: "examples.article.toMarkdown",
  usage: new TranslatableMarkup("Retrieves an article node and formats it as markdown"),
  access: ["access content"],
  params: [
    'nid' => new JsonRpcParameterDefinition(
      'nid',
      ["type" => "integer", "minimum" => 1],
      NULL,
      new TranslatableMarkup("The node ID of the article"),
      TRUE
    ),
  ]
)]
// TODO: Replace with actual #[McpTool] attribute once implemented
// #[McpTool(
//   title: "Get Article as Markdown",
//   annotations: ['category' => 'content', 'returns' => 'markdown']
// )]
class ArticleToMarkdown extends JsonRpcMethodBase {

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

  public function execute(ParameterBag $params): string {
    $nid = $params->get('nid');

    // Load node
    $node = $this->entityTypeManager->getStorage('node')->load($nid);

    if (!$node instanceof NodeInterface) {
      throw JsonRpcException::fromError(
        Error::invalidParams("Node with ID {$nid} not found")
      );
    }

    // Check bundle type
    if ($node->bundle() !== 'article') {
      throw JsonRpcException::fromError(
        Error::invalidParams("Node {$nid} is not an article")
      );
    }

    // Check access
    if (!$node->access('view')) {
      throw JsonRpcException::fromError(
        Error::invalidParams("Access denied to node {$nid}")
      );
    }

    return $this->convertToMarkdown($node);
  }

  protected function convertToMarkdown(NodeInterface $node): string {
    $title = $node->getTitle();
    $body = $node->get('body')->value ?? '';

    // Replace closing </p> followed by opening <p> with double newlines
    $body = preg_replace('/<\/p>\s*<p[^>]*>/', "\n\n", $body);

    // Remove remaining paragraph tags
    $body = preg_replace('/<\/?p[^>]*>/', '', $body);

    // Strip all remaining HTML tags
    $body = strip_tags($body);

    // Trim and normalize whitespace
    $body = trim($body);

    return "# {$title}\n\n{$body}";
  }

  public static function outputSchema(): array {
    return [
      'type' => 'string',
      'description' => 'Article content formatted as markdown',
    ];
  }

}
```

### Key Implementation Points

1. **Service Injection**: Use EntityTypeManagerInterface for node loading
2. **Error Handling**: Three error cases - node not found, wrong type, access denied
3. **Access Control**: Use Drupal's node access system via `$node->access('view')`
4. **Markdown Conversion**:
   - Convert `</p><p>` to `\n\n` first (preserves paragraph breaks)
   - Strip remaining `<p>` tags
   - Strip all other HTML tags
   - Trim whitespace
5. **Output Schema**: Document that method returns a string

### Testing the Method

Manual test via drush:

```bash
drush jsonrpc:request examples.article.toMarkdown '{"nid": 1}'
```

Expected output format:

```
# Article Title

First paragraph content here.

Second paragraph content here.
```

</details>
