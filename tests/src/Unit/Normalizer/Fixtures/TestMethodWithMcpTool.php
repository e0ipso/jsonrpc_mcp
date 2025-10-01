<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonrpc_mcp\Unit\Normalizer\Fixtures;

use Drupal\jsonrpc_mcp\Attribute\McpTool;

/**
 * Test class with McpTool attribute for normalizer testing.
 */
#[McpTool(
  title: 'Test Method Title',
  annotations: ['category' => 'test', 'priority' => 'high']
)]
class TestMethodWithMcpTool {
}
