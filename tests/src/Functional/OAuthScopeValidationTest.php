<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonrpc_mcp\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests for OAuth scope validation and authentication metadata.
 *
 * @group jsonrpc_mcp
 */
class OAuthScopeValidationTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'field',
    'text',
    'jsonrpc',
    'jsonrpc_mcp',
    'jsonrpc_mcp_auth_test',
  ];

  /**
   * Tests OAuth scope authentication system integration.
   *
   * Validates:
   * - Tools with auth metadata include it in discovery responses
   * - Tools without auth metadata don't have auth annotations
   * - Inferred auth level (scopes without explicit level)
   * - Auth metadata structure validation across all tools.
   */
  public function testOauthScopeAuthentication(): void {
    // Create admin user once for all assertions.
    $admin = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($admin);

    // Fetch tools list once.
    $tools = $this->getToolsList();

    // Test 1: Tools with explicit auth metadata.
    $tool_with_auth = $this->findToolByName($tools, 'test.methodWithAuth');
    $this->assertNotNull($tool_with_auth, 'Should find test.methodWithAuth');
    $this->assertAuthMetadata(
      $tool_with_auth,
      ['content:read', 'content:write'],
      'required',
      'Tools with auth must include metadata in annotations'
    );

    // Test 2: Tools without auth metadata.
    $tool_without_auth = $this->findToolByName($tools, 'test.methodWithoutAuth');
    $this->assertNotNull($tool_without_auth, 'Should find test.methodWithoutAuth');
    if (isset($tool_without_auth['annotations'])) {
      $this->assertArrayNotHasKey(
        'auth',
        $tool_without_auth['annotations'],
        'Tools without auth should not have auth annotation'
      );
    }

    // Test 3: Inferred auth level (scopes present, level not explicit).
    $tool_inferred = $this->findToolByName($tools, 'test.methodWithInferredAuth');
    $this->assertNotNull($tool_inferred, 'Should find test.methodWithInferredAuth');
    $this->assertArrayHasKey('annotations', $tool_inferred);
    $this->assertArrayHasKey('auth', $tool_inferred['annotations']);
    $auth = $tool_inferred['annotations']['auth'];
    $this->assertEquals(['user:read'], $auth['scopes']);
    $this->assertArrayNotHasKey(
      'level',
      $auth,
      'Inferred auth should not have explicit level'
    );

    // Test 4: Validate auth metadata structure across all tools.
    $this->validateAuthMetadataStructure($tools);
  }

  /**
   * Helper: Fetches and decodes the tools list.
   *
   * @return array
   *   Decoded tools array.
   */
  protected function getToolsList(): array {
    $this->drupalGet('/mcp/tools/list');
    $this->assertSession()->statusCodeEquals(200);

    $response = $this->getSession()->getPage()->getContent();
    $data = json_decode($response, TRUE);

    $this->assertIsArray($data);
    $this->assertArrayHasKey('tools', $data);

    return $data['tools'];
  }

  /**
   * Helper: Finds a tool by name in the tools array.
   *
   * @param array $tools
   *   Tools array from /mcp/tools/list.
   * @param string $name
   *   Tool name to find.
   *
   * @return array|null
   *   Tool definition or NULL if not found.
   */
  protected function findToolByName(array $tools, string $name): ?array {
    $filtered = array_filter($tools, function ($tool) use ($name) {
      return ($tool['name'] ?? '') === $name;
    });

    return !empty($filtered) ? reset($filtered) : NULL;
  }

  /**
   * Helper: Asserts auth metadata matches expectations.
   *
   * @param array $tool
   *   Tool definition.
   * @param array $expectedScopes
   *   Expected scopes array.
   * @param string $expectedLevel
   *   Expected auth level.
   * @param string $message
   *   Assertion message.
   */
  protected function assertAuthMetadata(
    array $tool,
    array $expectedScopes,
    string $expectedLevel,
    string $message = '',
  ): void {
    $this->assertArrayHasKey('annotations', $tool, $message);
    $this->assertArrayHasKey('auth', $tool['annotations'], $message);

    $auth = $tool['annotations']['auth'];
    $this->assertEquals($expectedScopes, $auth['scopes'], $message);
    $this->assertEquals($expectedLevel, $auth['level'], $message);
  }

  /**
   * Helper: Validates auth metadata structure across all tools.
   *
   * @param array $tools
   *   Tools array.
   */
  protected function validateAuthMetadataStructure(array $tools): void {
    foreach ($tools as $tool) {
      if (isset($tool['annotations']['auth'])) {
        $auth = $tool['annotations']['auth'];
        $tool_name = $tool['name'];

        // Auth must have scopes array.
        $this->assertArrayHasKey(
          'scopes',
          $auth,
          "Tool {$tool_name} auth must have scopes"
        );
        $this->assertIsArray(
          $auth['scopes'],
          "Tool {$tool_name} scopes must be array"
        );

        // If level present, must be valid.
        if (isset($auth['level'])) {
          $this->assertContains(
            $auth['level'],
            ['none', 'optional', 'required'],
            "Tool {$tool_name} auth level must be valid"
          );
        }

        // Description should be string if present.
        if (isset($auth['description'])) {
          $this->assertIsString(
            $auth['description'],
            "Tool {$tool_name} auth description must be string"
          );
        }
      }
    }
  }

}
