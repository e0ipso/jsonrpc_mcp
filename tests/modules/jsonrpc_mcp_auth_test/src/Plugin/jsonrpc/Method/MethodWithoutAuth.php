<?php

declare(strict_types=1);

namespace Drupal\jsonrpc_mcp_auth_test\Plugin\jsonrpc\Method;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\jsonrpc\Attribute\JsonRpcMethod;
use Drupal\jsonrpc\JsonRpcObject\ParameterBag;
use Drupal\jsonrpc\Plugin\JsonRpcMethodBase;
use Drupal\jsonrpc_mcp\Attribute\McpTool;

/**
 * Test method without authentication metadata.
 */
#[JsonRpcMethod(
  id: 'test.methodWithoutAuth',
  usage: new TranslatableMarkup('Test method without authentication'),
  access: ['access content'],
  params: []
)]
#[McpTool(
  title: 'Method Without Auth',
  annotations: [
    'category' => 'testing',
  ]
)]
class MethodWithoutAuth extends JsonRpcMethodBase {

  /**
   * {@inheritdoc}
   */
  public function execute(ParameterBag $params): array {
    return ['result' => 'success'];
  }

  /**
   * {@inheritdoc}
   */
  public static function outputSchema(): array {
    return [
      'type' => 'object',
      'properties' => [
        'result' => ['type' => 'string'],
      ],
    ];
  }

}
