<?php

namespace Drupal\jsonrpc_mcp_test\Plugin\jsonrpc\Method;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\jsonrpc\Attribute\JsonRpcMethod;
use Drupal\jsonrpc\Plugin\JsonRpcMethodBase;
use Drupal\jsonrpc_mcp\Attribute\McpTool;
use Drupal\jsonrpc\JsonRpcObject\ParameterBag;

/**
 * Test JSON-RPC method requiring admin permission.
 *
 * This method has McpTool attribute but requires 'administer site configuration'
 * permission, so should only be discovered for users with that permission.
 */
#[JsonRpcMethod(
  id: "test.adminOnly",
  usage: new TranslatableMarkup("Admin-only test method"),
  access: ["administer site configuration"],
  params: []
)]
#[McpTool(
  title: "Admin Only Tool",
  annotations: ['category' => 'admin']
)]
class AdminOnlyMethod extends JsonRpcMethodBase {

  /**
   * {@inheritdoc}
   */
  public function execute(ParameterBag $params): array {
    return ['result' => 'admin'];
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
