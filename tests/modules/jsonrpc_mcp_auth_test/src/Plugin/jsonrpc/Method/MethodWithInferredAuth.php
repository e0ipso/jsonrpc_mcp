<?php

declare(strict_types=1);

namespace Drupal\jsonrpc_mcp_auth_test\Plugin\jsonrpc\Method;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\jsonrpc\Attribute\JsonRpcMethod;
use Drupal\jsonrpc\JsonRpcObject\ParameterBag;
use Drupal\jsonrpc\Plugin\JsonRpcMethodBase;
use Drupal\jsonrpc_mcp\Attribute\McpTool;

/**
 * Test method with inferred auth level (scopes but no explicit level).
 */
#[JsonRpcMethod(
  id: 'test.methodWithInferredAuth',
  usage: new TranslatableMarkup('Test method with inferred auth level'),
  access: ['access content'],
  params: []
)]
#[McpTool(
  title: 'Method With Inferred Auth',
  annotations: [
    'category' => 'testing',
    'auth' => [
      'scopes' => ['user:read'],
      'description' => 'Inferred required level from scopes',
    ],
  ]
)]
class MethodWithInferredAuth extends JsonRpcMethodBase {

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
