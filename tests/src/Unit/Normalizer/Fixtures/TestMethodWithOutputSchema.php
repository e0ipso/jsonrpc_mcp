<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonrpc_mcp\Unit\Normalizer\Fixtures;

use Drupal\jsonrpc_mcp\Attribute\McpTool;

/**
 * Test class with outputSchema method for normalizer testing.
 */
#[McpTool(
  title: 'Test Method With Output',
  annotations: ['test' => TRUE]
)]
class TestMethodWithOutputSchema {

  /**
   * Returns the output schema.
   *
   * @return array
   *   The output schema.
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
