<?php

declare(strict_types=1);

namespace Drupal\jsonrpc_mcp_examples\Plugin\jsonrpc\Method;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\jsonrpc\Attribute\JsonRpcMethod;
use Drupal\jsonrpc\JsonRpcObject\ParameterBag;
use Drupal\jsonrpc\Plugin\JsonRpcMethodBase;
use Drupal\jsonrpc_mcp\Attribute\McpTool;
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
#[McpTool(
  title: "List Content Types",
  annotations: ['category' => 'discovery']
)]
class ListContentTypes extends JsonRpcMethodBase {

  public function __construct(
    array $configuration,
    string $plugin_id,
    $plugin_definition,
    protected EntityTypeBundleInfoInterface $bundleInfo,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    // @phpstan-ignore return.type
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
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

  /**
   * Returns the JSON Schema for the method's output.
   *
   * @return array
   *   A JSON Schema describing the output structure.
   */
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
