<?php

declare(strict_types=1);

namespace Drupal\jsonrpc_mcp\Plugin;

/**
 * Interface for MCP tool plugins with authentication support.
 */
interface McpToolInterface {

  /**
   * Returns authentication metadata for this tool.
   *
   * The auth metadata structure matches the MCP specification:
   * - scopes: Array of OAuth scope strings required
   * - description: Human-readable auth requirement description
   * - level: Optional explicit auth level ('none', 'optional', 'required')
   *
   * @return array|null
   *   Authentication metadata array, or NULL if no auth required.
   */
  public function getAuthMetadata(): ?array;

  /**
   * Returns the inferred authentication level for this tool.
   *
   * Authentication level is inferred from metadata:
   * - If scopes present but level undefined: 'required'
   * - If no scopes and no level: 'none'
   * - Explicit level in metadata overrides inference.
   *
   * @return string
   *   One of: 'none', 'optional', 'required'
   */
  public function getAuthLevel(): string;

  /**
   * Checks if this tool requires authentication.
   *
   * @return bool
   *   TRUE if authentication level is 'required', FALSE otherwise.
   */
  public function requiresAuthentication(): bool;

  /**
   * Returns the OAuth scopes required to invoke this tool.
   *
   * @return array
   *   Array of scope strings (e.g., ['content:read', 'user:write']).
   *   Empty array if no scopes required.
   */
  public function getRequiredScopes(): array;

}
