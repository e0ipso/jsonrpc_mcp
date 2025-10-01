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
/**
 * Placeholder for future McpTool attribute.
 *
 * @todo Replace with actual #[McpTool] attribute once implemented
 * #[McpTool(
 * title: "Get Article as Markdown",
 * annotations: ['category' => 'content', 'returns' => 'markdown']
 * )].
 */
class ArticleToMarkdown extends JsonRpcMethodBase {

  /**
   * Constructs an ArticleToMarkdown object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    // @phpstan-ignore-next-line return.type
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * Executes the method.
   *
   * @param \Drupal\jsonrpc\JsonRpcObject\ParameterBag $params
   *   The parameters for the method.
   *
   * @return string
   *   The article content formatted as markdown.
   *
   * @throws \Drupal\jsonrpc\Exception\JsonRpcException
   *   Thrown when the node is not found, not an article, or access is denied.
   */
  public function execute(ParameterBag $params): string {
    $nid = $params->get('nid');

    // Load node.
    $node = $this->entityTypeManager->getStorage('node')->load($nid);

    if (!$node instanceof NodeInterface) {
      throw JsonRpcException::fromError(
        Error::invalidParams("Node with ID {$nid} not found")
      );
    }

    // Check bundle type.
    if ($node->bundle() !== 'article') {
      throw JsonRpcException::fromError(
        Error::invalidParams("Node {$nid} is not an article")
      );
    }

    // Check access.
    if (!$node->access('view')) {
      throw JsonRpcException::fromError(
        Error::invalidParams("Access denied to node {$nid}")
      );
    }

    return $this->convertToMarkdown($node);
  }

  /**
   * Converts a node to markdown format.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to convert.
   *
   * @return string
   *   The markdown formatted content.
   */
  protected function convertToMarkdown(NodeInterface $node): string {
    $title = $node->getTitle();
    $body = $node->get('body')->value ?? '';

    // Replace closing </p> followed by opening <p> with double newlines.
    $body = preg_replace('/<\/p>\s*<p[^>]*>/', "\n\n", $body);

    // Remove remaining paragraph tags.
    $body = preg_replace('/<\/?p[^>]*>/', '', $body);

    // Strip all remaining HTML tags.
    $body = strip_tags($body);

    // Trim and normalize whitespace.
    $body = trim($body);

    return "# {$title}\n\n{$body}";
  }

  /**
   * Returns the output schema for this method.
   *
   * @return array
   *   The JSON Schema describing the output.
   */
  public static function outputSchema(): array {
    return [
      'type' => 'string',
      'description' => 'Article content formatted as markdown',
    ];
  }

}
