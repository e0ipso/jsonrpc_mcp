<?php

namespace Drupal\jsonrpc_mcp_test\Plugin\jsonrpc\Method;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\jsonrpc\Attribute\JsonRpcMethod;
use Drupal\jsonrpc\Plugin\JsonRpcMethodBase;
use Drupal\jsonrpc\JsonRpcObject\ParameterBag;

/**
 * Test JSON-RPC method WITHOUT McpTool attribute.
 *
 * This method should NOT be discovered by McpToolDiscoveryService.
 */
#[JsonRpcMethod(
  id: "test.unmarked",
  usage: new TranslatableMarkup("Method without MCP marking"),
  access: ["access content"],
  params: []
)]
class UnmarkedMethod extends JsonRpcMethodBase {

  /**
   * {@inheritdoc}
   */
  public function execute(ParameterBag $params): array {
    return ['result' => 'unmarked'];
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
