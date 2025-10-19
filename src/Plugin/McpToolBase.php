<?php

declare(strict_types=1);

namespace Drupal\jsonrpc_mcp\Plugin;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for MCP tool plugins.
 */
abstract class McpToolBase extends PluginBase implements McpToolInterface {

  /**
   * {@inheritdoc}
   */
  public function getAuthMetadata(): ?array {
    $definition = $this->getPluginDefinition();
    return $definition['annotations']['auth'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   *
   * Gets the authentication level with inference from scopes.
   * - If scopes present but level undefined: defaults to 'required'
   * - If no scopes and no level: defaults to 'none'
   * - Explicit level overrides inference.
   */
  public function getAuthLevel(): string {
    $auth = $this->getAuthMetadata();

    // No auth metadata at all.
    if (!$auth) {
      return 'none';
    }

    // Explicit level overrides inference.
    if (isset($auth['level'])) {
      return $auth['level'];
    }

    // Infer from scopes: if scopes present, default to 'required'.
    if (!empty($auth['scopes'])) {
      return 'required';
    }

    // No scopes, no explicit level.
    return 'none';
  }

  /**
   * {@inheritdoc}
   */
  public function requiresAuthentication(): bool {
    return $this->getAuthLevel() === 'required';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredScopes(): array {
    $auth = $this->getAuthMetadata();
    return $auth['scopes'] ?? [];
  }

}
