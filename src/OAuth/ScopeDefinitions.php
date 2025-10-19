<?php

declare(strict_types=1);

namespace Drupal\jsonrpc_mcp\OAuth;

/**
 * Defines available OAuth scopes for MCP tools.
 */
class ScopeDefinitions {

  /**
   * Returns all defined scopes.
   *
   * @return array
   *   Array of scope definitions keyed by scope name.
   */
  public static function getScopes(): array {
    return [
      'profile' => [
        'label' => 'User Profile',
        'description' => 'Access to user profile information',
      ],
      'content:read' => [
        'label' => 'Read Content',
        'description' => 'Read access to published content',
      ],
      'content:write' => [
        'label' => 'Write Content',
        'description' => 'Create and update content',
      ],
      'content:delete' => [
        'label' => 'Delete Content',
        'description' => 'Delete content',
      ],
      'content_type:read' => [
        'label' => 'Read Content Types',
        'description' => 'Access to content type definitions and configuration',
      ],
      'user:read' => [
        'label' => 'Read Users',
        'description' => 'Read user account information',
      ],
      'user:write' => [
        'label' => 'Write Users',
        'description' => 'Create and update user accounts',
      ],
      'admin:access' => [
        'label' => 'Administrative Access',
        'description' => 'Full administrative access to all content and configuration',
      ],
    ];
  }

  /**
   * Validates if a scope exists.
   *
   * @param string $scope
   *   The scope to validate.
   *
   * @return bool
   *   TRUE if scope is valid.
   */
  public static function isValid(string $scope): bool {
    return isset(self::getScopes()[$scope]);
  }

  /**
   * Returns scope information.
   *
   * @param string $scope
   *   The scope name.
   *
   * @return array|null
   *   Scope definition or NULL if not found.
   */
  public static function getScopeInfo(string $scope): ?array {
    $scopes = self::getScopes();
    return $scopes[$scope] ?? NULL;
  }

}
