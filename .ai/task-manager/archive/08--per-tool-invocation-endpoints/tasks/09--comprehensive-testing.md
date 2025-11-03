---
id: 9
group: 'testing'
dependencies: [8]
status: 'completed'
created: 2025-11-03
skills:
  - drupal-backend
  - phpunit
---

# Comprehensive Testing and RFC 6750 Validation

## Objective

Create integration tests covering per-tool invocation, OAuth2 authentication flows, scope validation, and RFC 6750 compliance for error responses.

## Skills Required

- **drupal-backend**: Drupal testing framework, kernel/functional tests
- **phpunit**: Test structure, assertions, test data setup

## Acceptance Criteria

- [ ] Kernel or functional test class created testing invocation flows
- [ ] Tests cover: dynamic route generation, POST/GET requests, OAuth2 token validation, scope validation
- [ ] Tests verify 401 responses for anonymous users and invalid tokens
- [ ] Tests verify 403 responses for insufficient scopes
- [ ] Tests verify WWW-Authenticate header format matches RFC 6750
- [ ] Tests verify successful invocation with valid token and scopes
- [ ] All existing tests continue to pass

## Technical Requirements

**Create test file** (choose appropriate test type):
- Functional test: `tests/src/Functional/PerToolInvocationTest.php` (full HTTP testing)
- OR Kernel test: `tests/src/Kernel/PerToolInvocationTest.php` (faster, no browser)

**Test coverage**:
1. Dynamic route generation for test tools
2. Anonymous user access to auth-required tool (expect 401)
3. Invalid OAuth2 token (expect 401 with `invalid_token`)
4. Valid token with insufficient scopes (expect 403 with `insufficient_scope`)
5. Valid token with correct scopes (expect successful invocation)
6. POST request with JSON body
7. GET request with query parameter
8. JSON-RPC error responses from handler

**Use existing test module**: `tests/modules/jsonrpc_mcp_auth_test` for test tools with auth requirements.

## Input Dependencies

- Task 8 complete (all implementation finished)
- Test module with auth-required tools exists
- Ability to create test OAuth2 tokens and scopes

## Output Artifacts

- Test file with comprehensive coverage
- All tests passing
- RFC 6750 compliance validated

<details>
<summary>Implementation Notes</summary>

## Meaningful Test Strategy Guidelines

**Your critical mantra: "write a few tests, mostly integration"**

Focus on:
- ✅ **Per-tool invocation flow** (custom business logic)
- ✅ **OAuth2 authentication workflow** (integration between modules)
- ✅ **RFC 6750 compliance** (critical authentication flow)
- ✅ **Scope-based authorization** (custom authorization logic)

Do NOT test:
- ❌ JSON-RPC module functionality (already tested upstream)
- ❌ simple_oauth token validation (already tested upstream)
- ❌ Drupal routing system (core functionality)
- ❌ Individual CRUD operations without custom logic

**Example Test Structure** (`tests/src/Functional/PerToolInvocationTest.php`):

```php
<?php

namespace Drupal\Tests\jsonrpc_mcp\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests per-tool invocation with OAuth2 authentication.
 *
 * @group jsonrpc_mcp
 */
class PerToolInvocationTest extends BrowserTestBase {

  protected static $modules = [
    'jsonrpc',
    'jsonrpc_mcp',
    'jsonrpc_mcp_test',
    'jsonrpc_mcp_auth_test',
    'simple_oauth',
  ];

  /**
   * Test anonymous user receives 401 for auth-required tool.
   */
  public function testAnonymousUserGets401(): void {
    // Make request to auth-required tool without authentication
    $response = $this->drupalGet('/mcp/tools/method.with.auth');

    // Assert 401 response
    $this->assertSession()->statusCodeEquals(401);

    // Assert WWW-Authenticate header format
    $header = $this->getSession()->getResponseHeader('WWW-Authenticate');
    $this->assertStringContainsString('Bearer realm="MCP Tools"', $header);
  }

  /**
   * Test invalid OAuth2 token receives 401 with invalid_token error.
   */
  public function testInvalidTokenGets401(): void {
    // Make request with invalid bearer token
    $this->drupalGet('/mcp/tools/method.with.auth', [], [
      'Authorization' => 'Bearer invalid_token_12345',
    ]);

    // Assert 401 with proper error
    $this->assertSession()->statusCodeEquals(401);
    $header = $this->getSession()->getResponseHeader('WWW-Authenticate');
    $this->assertStringContainsString('error="invalid_token"', $header);
  }

  /**
   * Test insufficient scopes receives 403 with missing scopes.
   */
  public function testInsufficientScopesGets403(): void {
    // Create token with limited scopes
    $token = $this->createTestToken(['read:content']);

    // Request tool requiring ['read:content', 'write:content']
    $response = $this->drupalPostForm(
      '/mcp/tools/method.with.auth',
      [
        'jsonrpc' => '2.0',
        'method' => 'method.with.auth',
        'params' => [],
        'id' => '1',
      ],
      NULL,
      [],
      ['Authorization' => 'Bearer ' . $token->getValue()]
    );

    // Assert 403 with missing scopes
    $this->assertSession()->statusCodeEquals(403);
    $header = $this->getSession()->getResponseHeader('WWW-Authenticate');
    $this->assertStringContainsString('error="insufficient_scope"', $header);
    $this->assertStringContainsString('scope="write:content"', $header);
  }

  /**
   * Test successful invocation with valid token and scopes.
   */
  public function testSuccessfulInvocation(): void {
    // Create token with all required scopes
    $token = $this->createTestToken(['read:content', 'write:content']);

    // POST request
    $response = $this->drupalPostForm(
      '/mcp/tools/method.with.auth',
      [
        'jsonrpc' => '2.0',
        'method' => 'method.with.auth',
        'params' => ['test' => 'value'],
        'id' => '1',
      ],
      NULL,
      [],
      ['Authorization' => 'Bearer ' . $token->getValue()]
    );

    // Assert successful response
    $this->assertSession()->statusCodeEquals(200);
    $body = json_decode($response, TRUE);
    $this->assertEquals('2.0', $body['jsonrpc']);
    $this->assertArrayHasKey('result', $body);
  }

  /**
   * Test GET request with query parameter.
   */
  public function testGetRequestWithQuery(): void {
    $token = $this->createTestToken(['read:content', 'write:content']);

    // URL-encode JSON-RPC payload
    $payload = json_encode([
      'jsonrpc' => '2.0',
      'method' => 'method.with.auth',
      'params' => [],
      'id' => '1',
    ]);
    $encoded = urlencode($payload);

    // GET request with query parameter
    $this->drupalGet("/mcp/tools/method.with.auth?query=$encoded", [], [
      'Authorization' => 'Bearer ' . $token->getValue(),
    ]);

    // Assert successful response
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Helper to create test OAuth2 token with scopes.
   */
  protected function createTestToken(array $scopes): object {
    // Implementation depends on simple_oauth test setup
    // Create oauth2_token entity with specified scopes
    // Return token entity
  }

}
```

**Test Execution**:
```bash
# Run only these tests
vendor/bin/phpunit --group jsonrpc_mcp

# Or run specific test file
vendor/bin/phpunit tests/src/Functional/PerToolInvocationTest.php
```

**Critical Test Scenarios**:
1. **Authentication flow** - Anonymous → 401
2. **Token validation** - Invalid token → 401 with `invalid_token`
3. **Scope validation** - Insufficient scopes → 403 with missing scopes
4. **Successful invocation** - Valid token + scopes → 200 with result
5. **GET vs POST** - Both methods work identically

**Do NOT test individually**:
- Token loading from database (simple_oauth responsibility)
- JSON-RPC batch execution (jsonrpc module responsibility)
- Route building (Drupal core responsibility)

**Focus on integration points** where our code coordinates between systems.
</details>
