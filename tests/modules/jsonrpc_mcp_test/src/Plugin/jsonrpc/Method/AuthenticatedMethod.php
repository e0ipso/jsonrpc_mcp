<?php

namespace Drupal\jsonrpc_mcp_test\Plugin\jsonrpc\Method;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\jsonrpc\Attribute\JsonRpcMethod;
use Drupal\jsonrpc\Plugin\JsonRpcMethodBase;
use Drupal\jsonrpc_mcp\Attribute\McpTool;
use Drupal\jsonrpc\JsonRpcObject\ParameterBag;

/**
 * Test JSON-RPC method requiring authenticated user.
 *
 * This method requires only 'access content' which authenticated users have.
 */
#[JsonRpcMethod(
  id: "test.authenticated",
  usage: new TranslatableMarkup("Authenticated user method"),
  access: ["access content"],
  params: []
)]
#[McpTool(
  title: "Authenticated User Tool",
  annotations: ['category' => 'authenticated']
)]
class AuthenticatedMethod extends JsonRpcMethodBase {

  /**
   * {@inheritdoc}
   */
  public function execute(ParameterBag $params): array {
    return ['result' => 'authenticated'];
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
