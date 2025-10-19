<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonrpc_mcp\Unit;

use Drupal\Tests\jsonrpc_mcp\Unit\Fixtures\TestMcpToolWithAuth;
use Drupal\Tests\jsonrpc_mcp\Unit\Fixtures\TestMcpToolWithoutAuth;
use Drupal\Tests\jsonrpc_mcp\Unit\Fixtures\TestMcpToolWithScopesNoLevel;
use Drupal\Tests\jsonrpc_mcp\Unit\Fixtures\TestMcpToolWithExplicitLevel;
use Drupal\Tests\jsonrpc_mcp\Unit\Fixtures\TestMcpToolWithEmptyScopes;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for McpToolBase authentication method inference logic.
 *
 * This test validates the complex inference logic for authentication levels:
 * - No auth metadata → level 'none'
 * - Auth with scopes, no level → inferred 'required'
 * - Auth with explicit level → use explicit
 * - Auth with empty scopes → level 'none'
 *
 * @group jsonrpc_mcp
 * @coversDefaultClass \Drupal\jsonrpc_mcp\Plugin\McpToolBase
 */
class McpToolBaseAuthTest extends UnitTestCase {

  /**
   * Tests getAuthLevel when no auth metadata is present.
   *
   * @covers ::getAuthLevel
   * @covers ::getAuthMetadata
   */
  public function testGetAuthLevelWithNoAuthMetadata(): void {
    $tool = new TestMcpToolWithoutAuth([], 'test_tool', []);
    $this->assertEquals('none', $tool->getAuthLevel());
    $this->assertNull($tool->getAuthMetadata());
  }

  /**
   * Tests getAuthLevel inference when scopes present but level undefined.
   *
   * Business rule: If scopes are present but level is not explicitly set,
   * the level should be inferred as 'required'.
   *
   * @covers ::getAuthLevel
   * @covers ::getAuthMetadata
   */
  public function testGetAuthLevelInferredFromScopes(): void {
    $tool = new TestMcpToolWithScopesNoLevel([], 'test_tool', [
      'annotations' => [
        'auth' => [
          'scopes' => ['content:read', 'content:write'],
          'description' => 'Content access',
        ],
      ],
    ]);

    $this->assertEquals('required', $tool->getAuthLevel());
    $this->assertIsArray($tool->getAuthMetadata());
  }

  /**
   * Tests getAuthLevel when explicit level is set.
   *
   * Explicit level should override inference logic.
   *
   * @covers ::getAuthLevel
   * @covers ::getAuthMetadata
   */
  public function testGetAuthLevelWithExplicitLevel(): void {
    // Explicit 'optional' level.
    $tool = new TestMcpToolWithExplicitLevel([], 'test_tool', [
      'annotations' => [
        'auth' => [
          'scopes' => ['content:read'],
          'level' => 'optional',
          'description' => 'Optional content access',
        ],
      ],
    ]);

    $this->assertEquals('optional', $tool->getAuthLevel());

    // Explicit 'none' level (overrides scopes).
    $tool_none = new TestMcpToolWithExplicitLevel([], 'test_tool_none', [
      'annotations' => [
        'auth' => [
          'scopes' => ['content:read'],
          'level' => 'none',
          'description' => 'No auth required',
        ],
      ],
    ]);

    $this->assertEquals('none', $tool_none->getAuthLevel());
  }

  /**
   * Tests getAuthLevel when scopes array is empty.
   *
   * @covers ::getAuthLevel
   * @covers ::getAuthMetadata
   */
  public function testGetAuthLevelWithEmptyScopes(): void {
    $tool = new TestMcpToolWithEmptyScopes([], 'test_tool', [
      'annotations' => [
        'auth' => [
          'scopes' => [],
          'description' => 'No scopes required',
        ],
      ],
    ]);

    $this->assertEquals('none', $tool->getAuthLevel());
  }

  /**
   * Tests requiresAuthentication method.
   *
   * @covers ::requiresAuthentication
   */
  public function testRequiresAuthentication(): void {
    // Tool with no auth.
    $tool_no_auth = new TestMcpToolWithoutAuth([], 'test_tool', []);
    $this->assertFalse($tool_no_auth->requiresAuthentication());

    // Tool with inferred 'required' level.
    $tool_required = new TestMcpToolWithScopesNoLevel([], 'test_tool', [
      'annotations' => [
        'auth' => [
          'scopes' => ['content:read'],
        ],
      ],
    ]);
    $this->assertTrue($tool_required->requiresAuthentication());

    // Tool with explicit 'optional' level.
    $tool_optional = new TestMcpToolWithExplicitLevel([], 'test_tool', [
      'annotations' => [
        'auth' => [
          'level' => 'optional',
        ],
      ],
    ]);
    $this->assertFalse($tool_optional->requiresAuthentication());
  }

  /**
   * Tests getRequiredScopes method.
   *
   * @covers ::getRequiredScopes
   */
  public function testGetRequiredScopes(): void {
    // Tool with scopes.
    $tool_with_scopes = new TestMcpToolWithAuth([], 'test_tool', [
      'annotations' => [
        'auth' => [
          'scopes' => ['content:read', 'content:write'],
        ],
      ],
    ]);
    $this->assertEquals(['content:read', 'content:write'], $tool_with_scopes->getRequiredScopes());

    // Tool without auth metadata.
    $tool_no_auth = new TestMcpToolWithoutAuth([], 'test_tool', []);
    $this->assertEquals([], $tool_no_auth->getRequiredScopes());

    // Tool with empty scopes.
    $tool_empty_scopes = new TestMcpToolWithEmptyScopes([], 'test_tool', [
      'annotations' => [
        'auth' => [
          'scopes' => [],
        ],
      ],
    ]);
    $this->assertEquals([], $tool_empty_scopes->getRequiredScopes());
  }

  /**
   * Tests complete auth metadata structure.
   *
   * @covers ::getAuthMetadata
   */
  public function testGetAuthMetadata(): void {
    $auth_definition = [
      'scopes' => ['content:read', 'user:read'],
      'level' => 'required',
      'description' => 'Read access to content and users',
    ];

    $tool = new TestMcpToolWithAuth([], 'test_tool', [
      'annotations' => [
        'auth' => $auth_definition,
      ],
    ]);

    $metadata = $tool->getAuthMetadata();
    $this->assertIsArray($metadata);
    $this->assertEquals($auth_definition['scopes'], $metadata['scopes']);
    $this->assertEquals($auth_definition['level'], $metadata['level']);
    $this->assertEquals($auth_definition['description'], $metadata['description']);
  }

}
