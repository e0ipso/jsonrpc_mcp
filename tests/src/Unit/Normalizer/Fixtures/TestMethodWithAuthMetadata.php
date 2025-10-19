<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonrpc_mcp\Unit\Normalizer\Fixtures;

use Drupal\jsonrpc_mcp\Attribute\McpTool;

/**
 * Test class with auth metadata in McpTool annotations.
 */
#[McpTool(
  title: 'Test Method With Auth',
  annotations: [
    'category' => 'test',
    'auth' => [
      'scopes' => ['content:read', 'user:write'],
      'description' => 'Requires content read and user write access',
    ],
  ]
)]
class TestMethodWithAuthMetadata {
}
