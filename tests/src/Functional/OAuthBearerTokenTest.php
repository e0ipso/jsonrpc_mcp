<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonrpc_mcp\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Tests\BrowserTestBase;
use GuzzleHttp\RequestOptions;

/**
 * Tests MCP endpoints with OAuth2 Bearer token authentication.
 *
 * This test verifies that OAuth2 authentication actually works,
 * not just cookie/session authentication.
 *
 * @group jsonrpc_mcp
 */
class OAuthBearerTokenTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'jsonrpc',
    'consumers',
    'simple_oauth',
    'simple_oauth_21',
    'jsonrpc_mcp',
  ];

  /**
   * Test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $testUser;

  /**
   * OAuth2 consumer (client).
   *
   * @var \Drupal\consumers\Entity\ConsumerInterface
   */
  protected $client;

  /**
   * OAuth2 access token.
   *
   * @var string
   */
  protected $accessToken;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a test user with the MCP discovery permission.
    $this->testUser = $this->drupalCreateUser([
      'access content',
      'access mcp tool discovery',
    ]);

    // Create OAuth2 client.
    $this->createOauthClient();

    // Generate access token.
    $this->generateAccessToken();
  }

  /**
   * Creates an OAuth2 consumer (client) entity.
   */
  protected function createOauthClient(): void {
    /** @var \Drupal\consumers\Entity\ConsumerInterface $client */
    $client = \Drupal::entityTypeManager()
      ->getStorage('consumer')
      ->create([
        'label' => 'Test Client',
        'user_id' => $this->testUser->id(),
      ]);
    $client->save();

    $this->client = $client;
  }

  /**
   * Generates an OAuth2 access token.
   */
  protected function generateAccessToken(): void {
    /** @var \Drupal\simple_oauth\Service\Oauth2TokenGeneratorInterface $tokenGenerator */
    $tokenGenerator = \Drupal::service('simple_oauth.oauth2_token_generator');

    // Generate token for our test user.
    $token = $tokenGenerator->generateAccessToken(
      $this->client,
      $this->testUser,
      []
    );

    $this->accessToken = $token->getToken()->toString();
  }

  /**
   * Tests MCP tools list endpoint with OAuth2 Bearer token.
   */
  public function testListEndpointWithOauthBearer(): void {
    $client = $this->getHttpClient();

    $response = $client->get($this->buildUrl('/mcp/tools/list'), [
      RequestOptions::HEADERS => [
        'Authorization' => 'Bearer ' . $this->accessToken,
        'Accept' => 'application/json',
      ],
      RequestOptions::HTTP_ERRORS => FALSE,
    ]);

    // Should get 200 OK with valid token.
    $this->assertEquals(200, $response->getStatusCode());

    $data = Json::decode((string) $response->getBody());
    $this->assertIsArray($data);
    $this->assertArrayHasKey('tools', $data);
    $this->assertIsArray($data['tools']);
  }

  /**
   * Tests MCP tools describe endpoint with OAuth2 Bearer token.
   *
   * This tests OAuth2 authentication works. Since no real JSON-RPC methods
   * are available without the test module, we expect a 404 for a non-existent
   * tool, but importantly NOT a 403 (which would indicate auth failed).
   */
  public function testDescribeEndpointWithOauthBearer(): void {
    $client = $this->getHttpClient();

    $response = $client->get($this->buildUrl('/mcp/tools/describe'), [
      RequestOptions::QUERY => ['name' => 'nonexistent.tool'],
      RequestOptions::HEADERS => [
        'Authorization' => 'Bearer ' . $this->accessToken,
        'Accept' => 'application/json',
      ],
      RequestOptions::HTTP_ERRORS => FALSE,
    ]);

    // Should get 404 (tool not found), NOT 403 (auth failed).
    // If we get 403, it means OAuth2 authentication isn't working.
    $this->assertEquals(404, $response->getStatusCode());

    $data = Json::decode((string) $response->getBody());
    $this->assertIsArray($data);
    $this->assertArrayHasKey('error', $data);
    $this->assertEquals('tool_not_found', $data['error']['code']);
  }

  /**
   * Tests MCP endpoints reject requests without authentication.
   */
  public function testEndpointsRejectUnauthenticated(): void {
    $client = $this->getHttpClient();

    // Test list endpoint without auth.
    $response = $client->get($this->buildUrl('/mcp/tools/list'), [
      RequestOptions::HEADERS => [
        'Accept' => 'application/json',
      ],
      RequestOptions::HTTP_ERRORS => FALSE,
    ]);

    // Should get 403 Forbidden without authentication.
    $this->assertEquals(403, $response->getStatusCode());

    // Test describe endpoint without auth.
    $response = $client->get($this->buildUrl('/mcp/tools/describe'), [
      RequestOptions::QUERY => ['name' => 'nonexistent.tool'],
      RequestOptions::HEADERS => [
        'Accept' => 'application/json',
      ],
      RequestOptions::HTTP_ERRORS => FALSE,
    ]);

    // Should get 403 Forbidden without authentication.
    $this->assertEquals(403, $response->getStatusCode());
  }

  /**
   * Tests MCP endpoints reject invalid Bearer tokens.
   */
  public function testEndpointsRejectInvalidToken(): void {
    $client = $this->getHttpClient();

    $response = $client->get($this->buildUrl('/mcp/tools/list'), [
      RequestOptions::HEADERS => [
        'Authorization' => 'Bearer invalid_token_12345',
        'Accept' => 'application/json',
      ],
      RequestOptions::HTTP_ERRORS => FALSE,
    ]);

    // Should get 401 or 403 with invalid token.
    $this->assertContains($response->getStatusCode(), [401, 403]);
  }

}
