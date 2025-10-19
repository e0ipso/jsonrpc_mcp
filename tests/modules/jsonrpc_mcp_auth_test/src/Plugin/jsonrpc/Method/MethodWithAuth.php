<?php

declare(strict_types=1);

namespace Drupal\jsonrpc_mcp_auth_test\Plugin\jsonrpc\Method;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\jsonrpc\Attribute\JsonRpcMethod;
use Drupal\jsonrpc\Attribute\JsonRpcParameterDefinition;
use Drupal\jsonrpc\JsonRpcObject\ParameterBag;
use Drupal\jsonrpc\Plugin\JsonRpcMethodBase;
use Drupal\jsonrpc_mcp\Attribute\McpTool;

/**
 * Test method with authentication metadata.
 */
#[JsonRpcMethod(
  id: 'test.methodWithAuth',
  usage: new TranslatableMarkup('Test method with authentication'),
  access: ['access content'],
  params: [
    'input' => new JsonRpcParameterDefinition(
      id: 'input',
      schema: ['type' => 'string'],
      factory: NULL,
      description: new TranslatableMarkup('Input parameter'),
      required: TRUE
    ),
  ]
)]
#[McpTool(
  title: 'Method With Auth',
  annotations: [
    'category' => 'testing',
    'auth' => [
      'scopes' => ['content:read', 'content:write'],
      'description' => 'Requires content read and write access',
      'level' => 'required',
    ],
  ]
)]
class MethodWithAuth extends JsonRpcMethodBase {

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
