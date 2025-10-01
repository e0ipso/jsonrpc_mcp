<?php

namespace Drupal\jsonrpc_mcp_test\Plugin\jsonrpc\Method;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\jsonrpc\Attribute\JsonRpcMethod;
use Drupal\jsonrpc\Attribute\JsonRpcParameterDefinition;
use Drupal\jsonrpc_mcp\Attribute\McpTool;
use Drupal\jsonrpc\Plugin\JsonRpcMethodBase;
use Drupal\jsonrpc\JsonRpcObject\ParameterBag;

/**
 * Test JSON-RPC method for MCP discovery testing.
 */
#[JsonRpcMethod(
  id: "test.example",
  usage: new TranslatableMarkup("Test method for MCP discovery"),
  access: ["access content"],
  params: [
    'input' => new JsonRpcParameterDefinition(
      'input',
      ["type" => "string"],
      NULL,
      new TranslatableMarkup("Test input parameter"),
      TRUE
    ),
  ]
)]
#[McpTool(
  title: "Test MCP Tool",
  annotations: ['category' => 'testing', 'test' => TRUE]
)]
class TestExampleMethod extends JsonRpcMethodBase {

  /**
   * {@inheritdoc}
   */
  public function execute(ParameterBag $params): array {
    return ['result' => $params->get('input')];
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
