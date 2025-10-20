---
id: 5
group: 'oauth-scope-system'
dependencies: [1, 3]
status: 'completed'
created: '2025-10-19'
skills:
  - php
  - drupal-backend
---

# Create OAuth Scope Validation Middleware

## Objective

Implement HTTP middleware to validate OAuth scopes before tool invocation, blocking unauthorized requests with detailed error messages.

## Skills Required

- **php**: Implementation of Symfony HttpKernel middleware
- **drupal-backend**: Integration with Drupal services and Simple OAuth module

## Acceptance Criteria

- [ ] File `src/Middleware/OAuthScopeValidator.php` is created
- [ ] Class implements `HttpKernelInterface`
- [ ] Middleware intercepts only `/mcp/tools/invoke` requests
- [ ] OAuth scopes are extracted from Bearer token via Simple OAuth
- [ ] Required scopes are validated against token scopes
- [ ] 403 error response returned when scopes are insufficient
- [ ] Error response includes required_scopes, missing_scopes, current_scopes
- [ ] Tools with level 'none' bypass validation
- [ ] Code follows Drupal coding standards

Use your internal Todo tool to track these and keep on track.

## Technical Requirements

- **Namespace**: `Drupal\jsonrpc_mcp\Middleware`
- **Class name**: `OAuthScopeValidator`
- **Implements**: `Symfony\Component\HttpKernel\HttpKernelInterface`
- **Dependencies**: HttpKernelInterface (wrapped), tool discovery service
- **Error code**: -32000 (JSON-RPC custom error)

## Input Dependencies

- Task 1: Requires ScopeDefinitions for scope validation
- Task 3: Requires tool instances with getRequiredScopes() and requiresAuthentication()

## Output Artifacts

- `src/Middleware/OAuthScopeValidator.php` - Registered in services in task 6

<details>
<summary>Implementation Notes</summary>

### File Location

Create file at: `src/Middleware/OAuthScopeValidator.php`

### Middleware Implementation

```php
<?php

declare(strict_types=1);

namespace Drupal\jsonrpc_mcp\Middleware;

use Drupal\Component\Serialization\Json;
use Drupal\jsonrpc_mcp\Service\McpToolDiscoveryService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Validates OAuth scopes for MCP tool invocations.
 */
class OAuthScopeValidator implements HttpKernelInterface {

  /**
   * Constructs a new OAuthScopeValidator.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $httpKernel
   *   The wrapped HTTP kernel.
   * @param \Drupal\jsonrpc_mcp\Service\McpToolDiscoveryService $toolDiscovery
   *   The tool discovery service.
   */
  public function __construct(
    protected HttpKernelInterface $httpKernel,
    protected McpToolDiscoveryService $toolDiscovery,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = TRUE): Response {
    // Only validate MCP tool invocations.
    if ($request->getPathInfo() !== '/mcp/tools/invoke') {
      return $this->httpKernel->handle($request, $type, $catch);
    }

    // Extract tool name from request.
    $content = $request->getContent();
    $data = Json::decode($content);
    $tool_name = $data['name'] ?? NULL;

    if (!$tool_name) {
      return $this->httpKernel->handle($request, $type, $catch);
    }

    // Get tool definition.
    $tools = $this->toolDiscovery->discoverTools();

    if (!isset($tools[$tool_name])) {
      return $this->httpKernel->handle($request, $type, $catch);
    }

    $tool = $tools[$tool_name];

    // Check if authentication is required.
    if (!method_exists($tool, 'requiresAuthentication') || !$tool->requiresAuthentication()) {
      return $this->httpKernel->handle($request, $type, $catch);
    }

    // Get token scopes.
    $token_scopes = $this->getTokenScopes($request);

    // Validate scopes.
    $required_scopes = method_exists($tool, 'getRequiredScopes') ? $tool->getRequiredScopes() : [];
    $missing_scopes = array_diff($required_scopes, $token_scopes);

    if (!empty($missing_scopes)) {
      return new JsonResponse([
        'jsonrpc' => '2.0',
        'error' => [
          'code' => -32000,
          'message' => 'Insufficient OAuth scopes',
          'data' => [
            'required_scopes' => $required_scopes,
            'missing_scopes' => array_values($missing_scopes),
            'current_scopes' => $token_scopes,
          ],
        ],
        'id' => $data['id'] ?? NULL,
      ], 403);
    }

    return $this->httpKernel->handle($request, $type, $catch);
  }

  /**
   * Extracts OAuth scopes from the request token.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return array
   *   Array of scope strings.
   */
  protected function getTokenScopes(Request $request): array {
    $authorization = $request->headers->get('Authorization');
    if (!$authorization || !str_starts_with($authorization, 'Bearer ')) {
      return [];
    }

    $token = substr($authorization, 7);

    // Use Simple OAuth to decode the token and extract scopes.
    if (!\Drupal::hasService('simple_oauth.oauth2_storage')) {
      return [];
    }

    try {
      $oauth_storage = \Drupal::service('simple_oauth.oauth2_storage');
      $access_token = $oauth_storage->getAccessToken($token);

      if (!$access_token) {
        return [];
      }

      // Extract scopes from token.
      $scopes = $access_token->getScopes();
      return array_map(fn($scope) => $scope->getIdentifier(), $scopes);
    }
    catch (\Exception $e) {
      // Token parsing failed, assume no scopes.
      return [];
    }
  }

}
```

### Key Implementation Details

1. **Path filtering**: Only intercept `/mcp/tools/invoke`, let other requests pass through
2. **Tool loading**: Use discovery service to get tool instance with auth methods
3. **Auth bypass**: Skip validation for tools with `requiresAuthentication() === FALSE`
4. **Scope extraction**: Use Simple OAuth service to parse Bearer token
5. **Error response**: Follow JSON-RPC 2.0 format with custom error code
6. **Graceful degradation**: Return empty scopes if Simple OAuth unavailable

### Error Handling

- Invalid JSON in request body: Pass to wrapped kernel (will return appropriate error)
- Tool not found: Pass to wrapped kernel (discovery service filters by permissions)
- Token parsing fails: Treat as empty scopes (will fail validation if scopes required)
- Simple OAuth service missing: Treat as empty scopes

### Verification

After implementation:

1. Run `vendor/bin/phpcs --standard=Drupal,DrupalPractice src/Middleware/OAuthScopeValidator.php`
2. Test with valid token containing required scopes (should pass through)
3. Test with token missing required scopes (should return 403)
4. Test without token on tool requiring auth (should return 403)
5. Test tool with level 'none' (should bypass validation)
</details>
