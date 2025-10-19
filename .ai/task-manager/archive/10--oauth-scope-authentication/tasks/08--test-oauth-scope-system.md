---
id: 8
group: "testing"
dependencies: [1, 2, 3, 4, 5, 6, 7]
status: "completed"
created: "2025-10-19"
skills:
  - phpunit
  - drupal-backend
---
# Test OAuth Scope Authentication System

## Objective
Create comprehensive tests for the OAuth scope authentication system covering unit, kernel, and functional test scenarios.

## Skills Required
- **phpunit**: Writing PHPUnit tests for Drupal with various test types
- **drupal-backend**: Understanding of Drupal testing framework, test base classes, and module testing patterns

## Acceptance Criteria
- [ ] Unit tests created for ScopeDefinitions class methods
- [ ] Unit tests created for McpToolBase auth method inference logic
- [ ] Kernel tests created for tool discovery with auth metadata
- [ ] Functional tests created for middleware scope validation
- [ ] Functional tests created for endpoint responses with auth metadata
- [ ] All tests pass: `vendor/bin/phpunit --group jsonrpc_mcp`
- [ ] Test coverage ≥80% for new components
- [ ] Code follows Drupal testing standards

Use your internal Todo tool to track these and keep on track.

## Technical Requirements
- **Test suites**: unit, kernel, functional
- **Group**: `@group jsonrpc_mcp`
- **Namespaces**: `Drupal\Tests\jsonrpc_mcp\Unit`, `Kernel`, `Functional`
- **Focus areas**: Scope validation, auth metadata, middleware behavior, endpoint responses

## Input Dependencies
- Tasks 1-7: All components must be implemented before testing

## Output Artifacts
- `tests/src/Unit/ScopeDefinitionsTest.php` - Unit tests for scope system
- `tests/src/Unit/McpToolBaseAuthTest.php` - Unit tests for auth inference
- `tests/src/Kernel/ToolDiscoveryAuthTest.php` - Kernel tests for discovery
- `tests/src/Functional/OAuthScopeValidationTest.php` - Functional tests for middleware

## Implementation Notes

### Meaningful Test Strategy Guidelines

Your critical mantra for test generation is: "write a few tests, mostly integration".

**Definition of "Meaningful Tests":**
Tests that verify custom business logic, critical paths, and edge cases specific to the application. Focus on testing YOUR code, not the framework or library functionality.

**When TO Write Tests:**
- Custom business logic and algorithms (auth level inference, scope validation)
- Critical user workflows and data transformations (middleware blocking, scope extraction)
- Edge cases and error conditions for core functionality (missing scopes, invalid tokens)
- Integration points between different system components (middleware + discovery service)
- Complex validation logic or calculations (scope comparison, level inference)

**When NOT to Write Tests:**
- Third-party library functionality (Simple OAuth, Symfony HttpKernel)
- Framework features (Drupal plugin system, entity storage)
- Simple getter/setter methods (getRequiredScopes returns array from metadata)
- Obvious functionality that would break immediately if incorrect

**Test Task Focus:**
- Combine related test scenarios into single test classes
- Focus on integration and critical path testing over unit test coverage
- Test middleware integration end-to-end rather than individual methods

<details>
<summary>Implementation Notes</summary>

### Test 1: Unit Tests for ScopeDefinitions
**File**: `tests/src/Unit/ScopeDefinitionsTest.php`

```php
<?php

namespace Drupal\Tests\jsonrpc_mcp\Unit;

use Drupal\jsonrpc_mcp\OAuth\ScopeDefinitions;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for OAuth scope definitions.
 *
 * @group jsonrpc_mcp
 */
class ScopeDefinitionsTest extends UnitTestCase {

  /**
   * Tests getScopes returns all defined scopes.
   */
  public function testGetScopes() {
    $scopes = ScopeDefinitions::getScopes();

    $this->assertIsArray($scopes);
    $this->assertCount(8, $scopes);
    $this->assertArrayHasKey('profile', $scopes);
    $this->assertArrayHasKey('content:read', $scopes);

    // Verify structure.
    $this->assertArrayHasKey('label', $scopes['profile']);
    $this->assertArrayHasKey('description', $scopes['profile']);
  }

  /**
   * Tests isValid with valid and invalid scopes.
   */
  public function testIsValid() {
    $this->assertTrue(ScopeDefinitions::isValid('profile'));
    $this->assertTrue(ScopeDefinitions::isValid('content:read'));
    $this->assertFalse(ScopeDefinitions::isValid('invalid:scope'));
    $this->assertFalse(ScopeDefinitions::isValid(''));
  }

  /**
   * Tests getScopeInfo returns correct data.
   */
  public function testGetScopeInfo() {
    $info = ScopeDefinitions::getScopeInfo('content:read');

    $this->assertIsArray($info);
    $this->assertEquals('Read Content', $info['label']);
    $this->assertStringContainsString('Read access', $info['description']);

    // Test invalid scope.
    $this->assertNull(ScopeDefinitions::getScopeInfo('invalid'));
  }

}
```

### Test 2: Unit Tests for Auth Level Inference
**File**: `tests/src/Unit/McpToolBaseAuthTest.php`

Test the complex inference logic:
- No auth metadata → level 'none'
- Auth with scopes, no level → inferred 'required'
- Auth with explicit level → use explicit
- Auth with empty scopes → level 'none'

### Test 3: Kernel Tests for Tool Discovery
**File**: `tests/src/Kernel/ToolDiscoveryAuthTest.php`

Test that:
- Tool discovery service finds tools with auth metadata
- Auth metadata is properly extracted from plugin definitions
- Tools without auth metadata return null

### Test 4: Functional Tests for Middleware
**File**: `tests/src/Functional/OAuthScopeValidationTest.php`

Test end-to-end scenarios:
- Tool with required scopes + valid token → pass through
- Tool with required scopes + missing scopes → 403 error
- Tool with required scopes + no token → 403 error
- Tool with level 'none' + no token → pass through
- Error response includes required_scopes, missing_scopes, current_scopes

### Test 5: Functional Tests for Endpoint Responses
**File**: `tests/src/Functional/ToolListAuthMetadataTest.php`

Test that:
- `/mcp/tools/list` includes auth metadata in annotations
- Tools without auth don't have auth annotation
- Auth metadata structure matches MCP specification

### Running Tests

```bash
# All OAuth scope tests
vendor/bin/phpunit --group jsonrpc_mcp

# Specific test suite
vendor/bin/phpunit tests/src/Unit/ScopeDefinitionsTest.php

# With coverage
vendor/bin/phpunit --group jsonrpc_mcp --coverage-html coverage/
```

### Coverage Targets
Aim for ≥80% coverage on:
- `src/OAuth/ScopeDefinitions.php` - 100% (simple class)
- `src/Plugin/McpToolBase.php` - 90% (auth methods)
- `src/Middleware/OAuthScopeValidator.php` - 85% (middleware logic)

Don't obsess over 100% coverage; focus on meaningful tests that catch real bugs.

### Verification
After implementation:
1. Run all tests: `vendor/bin/phpunit --group jsonrpc_mcp`
2. Check coverage: `vendor/bin/phpunit --group jsonrpc_mcp --coverage-text`
3. Verify tests follow Drupal standards: `vendor/bin/phpcs --standard=Drupal,DrupalPractice tests/`
4. Ensure all success criteria from plan are validated by tests
</details>
