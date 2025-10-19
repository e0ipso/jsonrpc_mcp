<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonrpc_mcp\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests for OAuth scope validation middleware.
 *
 * This test documents the expected behavior of the OAuth scope validation
 * system. Full end-to-end testing requires Simple OAuth module and token
 * infrastructure, which is not available in the standard test environment.
 *
 * The tests validate:
 * - Tools with auth metadata include it in discovery responses
 * - Tools without auth metadata work normally
 * - The system integrates correctly with the discovery service
 *
 * For complete OAuth integration testing in a production environment:
 * 1. Install Simple OAuth module
 * 2. Configure OAuth clients and scopes
 * 3. Generate access tokens with specific scopes
 * 4. Test that:
 *    - Requests with valid scopes succeed
 *    - Requests with missing scopes return 403 with error details
 *    - Requests without tokens for required tools return 403
 *    - Tools with level 'none' work without authentication
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
   * Tests that tools with auth metadata include it in discovery response.
   */
  public function testToolsListIncludesAuthMetadata(): void {
    // Create admin user with full permissions.
    $admin = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($admin);

    // Request tools list.
    $this->drupalGet('/mcp/tools/list');
    $this->assertSession()->statusCodeEquals(200);

    $response = $this->getSession()->getPage()->getContent();
    $data = json_decode($response, TRUE);

    $this->assertIsArray($data);
    $this->assertArrayHasKey('tools', $data);

    // Find the test method with auth.
    $tools_with_auth = array_filter($data['tools'], function ($tool) {
      return ($tool['name'] ?? '') === 'test.methodWithAuth';
    });

    $this->assertNotEmpty($tools_with_auth, 'Should find test.methodWithAuth in tools list');

    $tool = reset($tools_with_auth);

    // Verify auth metadata is present in annotations.
    $this->assertArrayHasKey('annotations', $tool);
    $this->assertArrayHasKey('auth', $tool['annotations']);

    $auth = $tool['annotations']['auth'];
    $this->assertEquals(['content:read', 'content:write'], $auth['scopes']);
    $this->assertEquals('required', $auth['level']);
  }

  /**
   * Tests that tools without auth metadata don't have auth annotations.
   */
  public function testToolsWithoutAuthMetadata(): void {
    $admin = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($admin);

    $this->drupalGet('/mcp/tools/list');
    $this->assertSession()->statusCodeEquals(200);

    $response = $this->getSession()->getPage()->getContent();
    $data = json_decode($response, TRUE);

    // Find the test method without auth.
    $tools_without_auth = array_filter($data['tools'], function ($tool) {
      return ($tool['name'] ?? '') === 'test.methodWithoutAuth';
    });

    $this->assertNotEmpty($tools_without_auth, 'Should find test.methodWithoutAuth in tools list');

    $tool = reset($tools_without_auth);

    // Verify no auth metadata in annotations.
    if (isset($tool['annotations'])) {
      $this->assertArrayNotHasKey('auth', $tool['annotations']);
    }
  }

  /**
   * Tests that inferred auth level is not explicitly included.
   */
  public function testInferredAuthLevel(): void {
    $admin = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($admin);

    $this->drupalGet('/mcp/tools/list');
    $this->assertSession()->statusCodeEquals(200);

    $response = $this->getSession()->getPage()->getContent();
    $data = json_decode($response, TRUE);

    // Find the test method with inferred auth.
    $tools_inferred = array_filter($data['tools'], function ($tool) {
      return ($tool['name'] ?? '') === 'test.methodWithInferredAuth';
    });

    $this->assertNotEmpty($tools_inferred, 'Should find test.methodWithInferredAuth in tools list');

    $tool = reset($tools_inferred);

    // Verify auth metadata structure (scopes present, level not explicit).
    $this->assertArrayHasKey('annotations', $tool);
    $this->assertArrayHasKey('auth', $tool['annotations']);

    $auth = $tool['annotations']['auth'];
    $this->assertEquals(['user:read'], $auth['scopes']);

    // Level should not be explicitly set (will be inferred as 'required').
    $this->assertArrayNotHasKey('level', $auth);
  }

  /**
   * Documents expected OAuth validation behavior in production.
   *
   * This test serves as documentation for the expected behavior when
   * Simple OAuth is properly configured. It cannot be fully tested
   * without OAuth infrastructure.
   */
  public function testExpectedOAuthBehaviorDocumentation(): void {
    $this->markTestSkipped('This test documents expected OAuth behavior that requires Simple OAuth module.');

    // The following scenarios should work in production with Simple OAuth:
    //
    // Scenario 1: Valid token with required scopes
    // POST /mcp/tools/invoke
    // Headers: Authorization: Bearer {token_with_content_read_write}
    // Body: {"name": "test.methodWithAuth", "arguments": {"input": "test"}}
    // Expected: 200 OK with result
    //
    // Scenario 2: Token missing required scopes
    // POST /mcp/tools/invoke
    // Headers: Authorization: Bearer {token_with_only_user_read}
    // Body: {"name": "test.methodWithAuth", "arguments": {"input": "test"}}
    // Expected: 403 Forbidden with error:
    // {
    //   "jsonrpc": "2.0",
    //   "error": {
    //     "code": -32000,
    //     "message": "Insufficient OAuth scopes",
    //     "data": {
    //       "required_scopes": ["content:read", "content:write"],
    //       "missing_scopes": ["content:read", "content:write"],
    //       "current_scopes": ["user:read"]
    //     }
    //   },
    //   "id": 1
    // }
    //
    // Scenario 3: No token for required tool
    // POST /mcp/tools/invoke
    // Body: {"name": "test.methodWithAuth", "arguments": {"input": "test"}}
    // Expected: 403 Forbidden with error (missing_scopes = required_scopes)
    //
    // Scenario 4: Tool without auth requirements
    // POST /mcp/tools/invoke
    // Body: {"name": "test.methodWithoutAuth", "arguments": {}}
    // Expected: 200 OK with result (no token needed)
    //
    // To implement these tests in production:
    // 1. Enable Simple OAuth module
    // 2. Create OAuth client via /oauth/token endpoint
    // 3. Configure scopes in Simple OAuth settings
    // 4. Generate tokens with specific scopes
    // 5. Use Guzzle to make authenticated requests
    // 6. Assert response codes and error structures
  }

  /**
   * Tests auth metadata structure validation.
   */
  public function testAuthMetadataStructure(): void {
    $admin = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($admin);

    $this->drupalGet('/mcp/tools/list');
    $response = $this->getSession()->getPage()->getContent();
    $data = json_decode($response, TRUE);

    foreach ($data['tools'] as $tool) {
      if (isset($tool['annotations']['auth'])) {
        $auth = $tool['annotations']['auth'];

        // Auth must have scopes array.
        $this->assertArrayHasKey('scopes', $auth, "Tool {$tool['name']} auth must have scopes");
        $this->assertIsArray($auth['scopes'], "Tool {$tool['name']} scopes must be array");

        // If level present, must be valid.
        if (isset($auth['level'])) {
          $this->assertContains(
            $auth['level'],
            ['none', 'optional', 'required'],
            "Tool {$tool['name']} auth level must be valid"
          );
        }

        // Description should be present and string.
        if (isset($auth['description'])) {
          $this->assertIsString($auth['description'], "Tool {$tool['name']} auth description must be string");
        }
      }
    }
  }

}
