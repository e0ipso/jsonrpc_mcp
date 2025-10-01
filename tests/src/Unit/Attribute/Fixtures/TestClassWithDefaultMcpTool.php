<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonrpc_mcp\Unit\Attribute\Fixtures;

use Drupal\jsonrpc_mcp\Attribute\McpTool;

/**
 * Test class with default McpTool attribute for reflection testing.
 */
#[McpTool]
class TestClassWithDefaultMcpTool {
}
