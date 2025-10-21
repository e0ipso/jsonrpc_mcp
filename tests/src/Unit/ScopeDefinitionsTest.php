<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonrpc_mcp\Unit;

use Drupal\jsonrpc_mcp\OAuth\ScopeDefinitions;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for OAuth scope definitions.
 *
 * @group jsonrpc_mcp
 * @coversDefaultClass \Drupal\jsonrpc_mcp\OAuth\ScopeDefinitions
 */
class ScopeDefinitionsTest extends UnitTestCase {

  /**
   * Tests getScopes returns all defined scopes.
   *
   * @covers ::getScopes
   */
  public function testGetScopes(): void {
    $scopes = ScopeDefinitions::getScopes();

    $this->assertIsArray($scopes);
    $this->assertCount(8, $scopes);
    $this->assertArrayHasKey('profile', $scopes);
    $this->assertArrayHasKey('content:read', $scopes);
    $this->assertArrayHasKey('content:write', $scopes);
    $this->assertArrayHasKey('content:delete', $scopes);
    $this->assertArrayHasKey('content_type:read', $scopes);
    $this->assertArrayHasKey('user:read', $scopes);
    $this->assertArrayHasKey('user:write', $scopes);
    $this->assertArrayHasKey('admin:access', $scopes);

    // Verify structure of each scope.
    foreach ($scopes as $scope_name => $scope_info) {
      $this->assertArrayHasKey('label', $scope_info, "Scope '$scope_name' must have a label");
      $this->assertArrayHasKey('description', $scope_info, "Scope '$scope_name' must have a description");
      $this->assertIsString($scope_info['label']);
      $this->assertIsString($scope_info['description']);
      $this->assertNotEmpty($scope_info['label']);
      $this->assertNotEmpty($scope_info['description']);
    }
  }

  /**
   * Tests isValid with valid scopes.
   *
   * @covers ::isValid
   */
  public function testIsValidWithValidScopes(): void {
    $this->assertTrue(ScopeDefinitions::isValid('profile'));
    $this->assertTrue(ScopeDefinitions::isValid('content:read'));
    $this->assertTrue(ScopeDefinitions::isValid('content:write'));
    $this->assertTrue(ScopeDefinitions::isValid('content:delete'));
    $this->assertTrue(ScopeDefinitions::isValid('content_type:read'));
    $this->assertTrue(ScopeDefinitions::isValid('user:read'));
    $this->assertTrue(ScopeDefinitions::isValid('user:write'));
    $this->assertTrue(ScopeDefinitions::isValid('admin:access'));
  }

  /**
   * Tests isValid with invalid scopes.
   *
   * @covers ::isValid
   */
  public function testIsValidWithInvalidScopes(): void {
    $this->assertFalse(ScopeDefinitions::isValid('invalid:scope'));
    $this->assertFalse(ScopeDefinitions::isValid(''));
    $this->assertFalse(ScopeDefinitions::isValid('content'));
    $this->assertFalse(ScopeDefinitions::isValid('user'));
    $this->assertFalse(ScopeDefinitions::isValid('admin'));
  }

  /**
   * Tests getScopeInfo returns correct data for valid scopes.
   *
   * @covers ::getScopeInfo
   */
  public function testGetScopeInfoWithValidScope(): void {
    $info = ScopeDefinitions::getScopeInfo('content:read');

    $this->assertIsArray($info);
    $this->assertEquals('Read Content', $info['label']);
    $this->assertStringContainsString('Read access', $info['description']);

    // Test another scope.
    $info = ScopeDefinitions::getScopeInfo('admin:access');
    $this->assertIsArray($info);
    $this->assertEquals('Administrative Access', $info['label']);
    $this->assertStringContainsString('administrative access', $info['description']);
  }

  /**
   * Tests getScopeInfo returns null for invalid scopes.
   *
   * @covers ::getScopeInfo
   */
  public function testGetScopeInfoWithInvalidScope(): void {
    $this->assertNull(ScopeDefinitions::getScopeInfo('invalid'));
    $this->assertNull(ScopeDefinitions::getScopeInfo(''));
    $this->assertNull(ScopeDefinitions::getScopeInfo('nonexistent:scope'));
  }

}
