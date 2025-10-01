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
 * - Structured output with multiple fields.
 *
 * @todo Replace with actual #[McpTool] attribute once implemented:
 * #[McpTool(
 *   title: "List Articles",
 *   annotations: ['category' => 'content', 'supports_pagination' => true]
 * )]
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
class ListArticles extends JsonRpcMethodBase {

  /**
   * Constructs a new ListArticles method.
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
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ParameterBag $params): array {
    $pagination = $params->get('page');

    // Build entity query.
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'article')
      ->condition('status', 1)
      ->accessCheck(TRUE)
      ->sort('created', 'DESC');

    // Apply pagination if provided.
    if ($pagination) {
      $query->range($pagination['offset'], $pagination['limit']);
    }

    $nids = $query->execute();

    if (empty($nids)) {
      return [];
    }

    // Load nodes.
    $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);

    $result = [];
    foreach ($nodes as $node) {
      // Double-check access (query accessCheck is applied, but be explicit).
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

  /**
   * {@inheritdoc}
   */
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
