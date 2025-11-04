<?php

declare(strict_types=1);

namespace Drupal\jsonrpc_mcp\Controller;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\jsonrpc\Controller\HttpController;
use Drupal\jsonrpc\Exception\ErrorHandler;
use Drupal\jsonrpc\HandlerInterface;
use Drupal\jsonrpc\JsonRpcObject\Error;
use Drupal\jsonrpc_mcp\Service\McpToolDiscoveryService;
use Drupal\simple_oauth\Entity\Oauth2TokenInterface;
use Drupal\simple_oauth\Server\ResourceServerFactoryInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Controller for per-tool MCP invocation endpoints.
 */
class McpToolInvokeController extends ControllerBase {

  /**
   * Constructs a new McpToolInvokeController.
   *
   * @param \Drupal\jsonrpc_mcp\Service\McpToolDiscoveryService $toolDiscovery
   *   The tool discovery service.
   * @param \Drupal\jsonrpc\HandlerInterface $handler
   *   The JSON-RPC handler.
   * @param \Drupal\jsonrpc\Controller\HttpController $jsonRpcController
   *   The JSON-RPC HTTP controller.
   * @param \Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface $httpMessageFactory
   *   The PSR-7 HTTP message factory.
   * @param \Drupal\simple_oauth\Server\ResourceServerFactoryInterface $resourceServerFactory
   *   The OAuth2 resource server factory.
   * @param \Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface $httpFoundationFactory
   *   The HTTP foundation factory.
   */
  public function __construct(
    protected McpToolDiscoveryService $toolDiscovery,
    protected HandlerInterface $handler,
    protected HttpController $jsonRpcController,
    protected HttpMessageFactoryInterface $httpMessageFactory,
    protected ResourceServerFactoryInterface $resourceServerFactory,
    protected HttpFoundationFactoryInterface $httpFoundationFactory,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    // OAuth library.
    return new static(
      $container->get('jsonrpc_mcp.tool_discovery'),
      $container->get('jsonrpc.handler'),
      new HttpController(
        $container->get('service_container'),
        $container->get('jsonrpc.handler'),
        $container->get('jsonrpc.schema_validator'),
        $container->get(ErrorHandler::class),
      ),
      $container->get('psr7.http_message_factory'),
      $container->get('simple_oauth.server.resource_server.factory'),
      $container->get('psr7.http_foundation_factory'),
    );
  }

  /**
   * Invokes a specific MCP tool with JSON-RPC payload.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request.
   * @param string $tool_name
   *   The tool name from the route.
   *
   * @return \Drupal\Core\Cache\CacheableResponseInterface
   *   The response.
   */
  public function invoke(Request $request, string $tool_name): CacheableResponseInterface {
    $tools = $this->toolDiscovery->discoverTools();

    if (!isset($tools[$tool_name])) {
      return $this->createErrorResponse(
        Error::METHOD_NOT_FOUND,
        'Method not found',
        NULL,
        404
      );
    }

    // Extract auth metadata from route defaults to avoid duplicate discovery.
    $route = $request->attributes->get('_route_object');
    $auth_metadata = $this->extractAuthMetadataFromRoute($route);

    $auth_response = $this->checkAuthenticationRequirements($auth_metadata, $request);
    if ($auth_response) {
      return $auth_response;
    }

    return $this->jsonRpcController->resolve($request);
  }

  /**
   * Validates OAuth2 token and returns token or error response.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Drupal\simple_oauth\Entity\Oauth2TokenInterface|\Drupal\Core\Cache\CacheableResponse
   *   The validated token or error response.
   */
  protected function validateOauth2Token(Request $request): Oauth2TokenInterface|CacheableResponse {
    // Update the request with the OAuth information.
    try {
      // Create a PSR-7 message from the request that is compatible with the
      // OAuth library.
      $psr7_request = $this->httpMessageFactory->createRequest($request);
      $resource_server = $this->resourceServerFactory->get();
      $output_psr7_request = $resource_server->validateAuthenticatedRequest($psr7_request);

      // Convert back to the Drupal/Symfony HttpFoundation objects.
      $auth_request = $this->httpFoundationFactory->createRequest($output_psr7_request);
    }
    catch (OAuthServerException $exception) {
      $response = new CacheableResponse('', 401, [
        'WWW-Authenticate' => 'Bearer realm="MCP Tools", error="invalid_token", error_description="The access token is invalid or expired"',
      ]);
      $response->getCacheableMetadata()->setCacheMaxAge(0)->addCacheContexts(['user']);
      return $response;
    }

    try {
      $token_storage = $this->entityTypeManager()->getStorage('oauth2_token');
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException  $e) {
      $response = new CacheableResponse('', 401, [
        'WWW-Authenticate' => 'Bearer realm="MCP Tools", error="invalid_token", error_description="Fatal server error"',
      ]);
      $response->getCacheableMetadata()->setCacheMaxAge(0)->addCacheContexts(['user']);
      return $response;
    }
    $tokens = $token_storage
      ->loadByProperties([
        'value' => $auth_request->get('oauth_access_token_id'),
      ]);
    $token = reset($tokens);

    if (!$token instanceof Oauth2TokenInterface || $token->isRevoked()) {
      $response = new CacheableResponse('', 401, [
        'WWW-Authenticate' => 'Bearer realm="MCP Tools", error="invalid_token", error_description="The access token is invalid or expired"',
      ]);
      $response->getCacheableMetadata()->setCacheMaxAge(0)->addCacheContexts(['user']);
      return $response;
    }

    return $token;
  }

  /**
   * Validates OAuth2 token scopes against required scopes.
   *
   * @param \Drupal\simple_oauth\Entity\Oauth2TokenInterface $token
   *   The OAuth2 token.
   * @param array $required_scopes
   *   Required scope IDs.
   *
   * @return \Drupal\Core\Cache\CacheableResponse|null
   *   Error response if scopes insufficient, NULL otherwise.
   */
  protected function validateOauth2Scopes(Oauth2TokenInterface $token, array $required_scopes): ?CacheableResponse {
    if (empty($required_scopes)) {
      return NULL;
    }

    $field_item_list = $token->get('scopes');
    $token_scope_entities = $this->entityTypeManager()->getStorage('oauth2_scope')->loadMultiple(
      array_map(
        fn (array $value) => $value['scope_id'],
        $field_item_list->getValue(),
      )
    );
    $token_scopes = array_map(
      fn($scope) => $scope->label(),
      $token_scope_entities,
    );
    $missing_scopes = array_diff($required_scopes, $token_scopes);

    if (empty($missing_scopes)) {
      return NULL;
    }

    $scope_string = implode(' ', $missing_scopes);
    $response = new CacheableResponse('', 403, [
      'WWW-Authenticate' => sprintf(
        'Bearer realm="MCP Tools", error="insufficient_scope", scope="%s"',
        $scope_string
      ),
    ]);
    $response->getCacheableMetadata()->setCacheMaxAge(0)->addCacheContexts(['user']);
    return $response;
  }

  /**
   * Extracts auth metadata from route defaults.
   *
   * @param \Symfony\Component\Routing\Route|null $route
   *   The route object.
   *
   * @return array
   *   Auth metadata array with 'level' and 'scopes' keys.
   */
  protected function extractAuthMetadataFromRoute(?Route $route): array {
    if (!$route) {
      return ['level' => NULL, 'scopes' => []];
    }

    return [
      'level' => $route->getDefault('_mcp_auth_level'),
      'scopes' => $route->getDefault('_mcp_auth_scopes') ?? [],
    ];
  }

  /**
   * Checks authentication requirements based on MCP tool annotations.
   *
   * @param array $auth_metadata
   *   Auth metadata array with 'level' and 'scopes' keys.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request.
   *
   * @return \Drupal\Core\Cache\CacheableResponse|null
   *   Error response if auth fails, NULL if auth passes.
   */
  protected function checkAuthenticationRequirements(array $auth_metadata, Request $request): ?CacheableResponse {
    $auth_level = $auth_metadata['level'] ?? NULL;
    $required_scopes = $auth_metadata['scopes'] ?? [];

    if ($auth_level === 'required' && $this->currentUser()->isAnonymous()) {
      $response = new CacheableResponse('', 401, [
        'WWW-Authenticate' => 'Bearer realm="MCP Tools"',
      ]);
      $response->getCacheableMetadata()->setCacheMaxAge(0)->addCacheContexts(['user']);
      return $response;
    }

    $authorization = $request->headers->get('Authorization');
    $is_bearer = $authorization && str_starts_with($authorization, 'Bearer ');

    if (!empty($required_scopes) && !$is_bearer) {
      $response = new CacheableResponse('', 401, [
        'WWW-Authenticate' => 'Bearer realm="MCP Tools"',
      ]);
      $response->getCacheableMetadata()->setCacheMaxAge(0)->addCacheContexts(['user']);
      return $response;
    }

    if (!$is_bearer) {
      return NULL;
    }

    $token_result = $this->validateOauth2Token($request);

    if ($token_result instanceof CacheableResponse) {
      return $token_result;
    }

    return $this->validateOauth2Scopes($token_result, $required_scopes);
  }

  /**
   * Creates a JSON-RPC error response.
   *
   * @param int $code
   *   The error code.
   * @param string $message
   *   The error message.
   * @param mixed $id
   *   The request ID.
   * @param int $http_status
   *   HTTP status code.
   *
   * @return \Drupal\Core\Cache\CacheableJsonResponse
   *   The error response.
   */
  protected function createErrorResponse(int $code, string $message, mixed $id = NULL, int $http_status = 400): CacheableJsonResponse {
    $response = new CacheableJsonResponse([
      'jsonrpc' => '2.0',
      'error' => [
        'code' => $code,
        'message' => $message,
      ],
      'id' => $id,
    ], $http_status);
    $response->getCacheableMetadata()->setCacheMaxAge(0);
    return $response;
  }

}
