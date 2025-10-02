<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonrpc_mcp_examples\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Functional tests for jsonrpc_mcp_examples methods.
 *
 * @group jsonrpc_mcp
 */
class ExampleMethodsTest extends BrowserTestBase {

  use NodeCreationTrait;

  /**
   * Test user for Basic Auth.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $testUser;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'jsonrpc',
    'jsonrpc_mcp',
    'jsonrpc_mcp_examples',
    'serialization',
    'basic_auth',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create article content type.
    NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ])->save();

    // Add body field to article content type.
    // Create the body field storage if it doesn't exist.
    if (!\Drupal::entityTypeManager()->getStorage('field_storage_config')->load('node.body')) {
      FieldStorageConfig::create([
        'entity_type' => 'node',
        'field_name' => 'body',
        'type' => 'text_with_summary',
      ])->save();
    }
    // Add body field instance for the article content type.
    FieldConfig::create([
      'field_name' => 'body',
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'Body',
    ])->save();
    // Assign widget settings for the 'default' form mode.
    \Drupal::service('entity_display.repository')->getFormDisplay('node', 'article')
      ->setComponent('body', ['type' => 'text_textarea_with_summary'])
      ->save();
    // Assign display settings for the 'default' view mode.
    \Drupal::service('entity_display.repository')->getViewDisplay('node', 'article')
      ->setComponent('body', ['label' => 'hidden', 'type' => 'text_default'])
      ->save();

    // Enable basic auth for JSON-RPC.
    $this->container->get('config.factory')
      ->getEditable('jsonrpc.settings')
      ->set('basic_auth', TRUE)
      ->save(TRUE);
    \Drupal::service('router.builder')->rebuild();

    // Create a user with JSON-RPC and content access permissions.
    // Store it so we can use it for authentication in requests.
    $this->testUser = $this->drupalCreateUser([
      'use jsonrpc services',
      'access content',
    ]);
  }

  /**
   * Tests ArticleToMarkdown with valid article.
   */
  public function testArticleToMarkdownSuccess(): void {
    $node = $this->createNode([
      'type' => 'article',
      'title' => 'Test Article',
      'body' => [
        'value' => '<p>First paragraph.</p><p>Second paragraph.</p>',
        'format' => 'full_html',
      ],
    ]);

    $request = [
      'jsonrpc' => '2.0',
      'method' => 'examples.article.toMarkdown',
      'params' => ['nid' => (int) $node->id()],
      'id' => 1,
    ];

    $data = $this->postJsonAndDecode('/jsonrpc', $request);

    $this->assertArrayNotHasKey('error', $data);
    $this->assertArrayHasKey('result', $data);
    $this->assertIsString($data['result']);
    $this->assertStringContainsString('# Test Article', $data['result']);
    $this->assertStringContainsString('First paragraph.', $data['result']);
  }

  /**
   * Tests ArticleToMarkdown with invalid node ID.
   */
  public function testArticleToMarkdownInvalidNode(): void {
    $request = [
      'jsonrpc' => '2.0',
      'method' => 'examples.article.toMarkdown',
      'params' => ['nid' => 99999],
      'id' => 1,
    ];

    $data = $this->postJsonAndDecode('/jsonrpc', $request);

    $this->assertArrayHasKey('error', $data);
    // Error could be in message or data field.
    $errorText = $data['error']['message'] ?? '';
    if (isset($data['error']['data'])) {
      $errorText .= ' ' . $data['error']['data'];
    }
    $this->assertStringContainsString('not found', $errorText);
  }

  /**
   * Tests ArticleToMarkdown with wrong content type.
   */
  public function testArticleToMarkdownWrongType(): void {
    // Create a different content type.
    NodeType::create([
      'type' => 'page',
      'name' => 'Basic Page',
    ])->save();

    $node = $this->createNode([
      'type' => 'page',
      'title' => 'Page Title',
    ]);

    $request = [
      'jsonrpc' => '2.0',
      'method' => 'examples.article.toMarkdown',
      'params' => ['nid' => (int) $node->id()],
      'id' => 1,
    ];

    $data = $this->postJsonAndDecode('/jsonrpc', $request);

    $this->assertArrayHasKey('error', $data);
    // Error could be in message or data field.
    $errorText = $data['error']['message'] ?? '';
    if (isset($data['error']['data'])) {
      $errorText .= ' ' . $data['error']['data'];
    }
    $this->assertStringContainsString('article', $errorText);
  }

  /**
   * Tests ArticleToMarkdown with complex HTML content.
   */
  public function testArticleToMarkdownComplexHtml(): void {
    $complex_html = '
      <h2>Section Header</h2>
      <p>Paragraph with <strong>bold</strong> and <em>italic</em> text.</p>
      <ul>
        <li>List item 1</li>
        <li>List item 2</li>
      </ul>
    ';

    $node = $this->createNode([
      'type' => 'article',
      'title' => 'Complex Article',
      'body' => [
        'value' => $complex_html,
        'format' => 'full_html',
      ],
    ]);

    $request = [
      'jsonrpc' => '2.0',
      'method' => 'examples.article.toMarkdown',
      'params' => ['nid' => (int) $node->id()],
      'id' => 1,
    ];

    $data = $this->postJsonAndDecode('/jsonrpc', $request);

    $this->assertArrayNotHasKey('error', $data);
    $this->assertIsString($data['result']);
  }

  /**
   * Tests ListContentTypes method.
   */
  public function testListContentTypes(): void {
    $request = [
      'jsonrpc' => '2.0',
      'method' => 'examples.contentTypes.list',
      'id' => 1,
    ];

    $data = $this->postJsonAndDecode('/jsonrpc', $request);

    $this->assertArrayNotHasKey('error', $data);
    $this->assertIsArray($data['result']);
    // Find article content type in results.
    $found = FALSE;
    foreach ($data['result'] as $type) {
      if ($type['id'] === 'article') {
        $this->assertSame('Article', $type['label']);
        $found = TRUE;
        break;
      }
    }
    $this->assertTrue($found, 'Article content type not found in results');
  }

  /**
   * Tests ListContentTypes with multiple content types.
   */
  public function testListContentTypesMultiple(): void {
    // Create additional content types.
    NodeType::create([
      'type' => 'page',
      'name' => 'Basic Page',
    ])->save();
    NodeType::create([
      'type' => 'blog',
      'name' => 'Blog Post',
    ])->save();

    $request = [
      'jsonrpc' => '2.0',
      'method' => 'examples.contentTypes.list',
      'id' => 1,
    ];

    $data = $this->postJsonAndDecode('/jsonrpc', $request);

    $this->assertArrayNotHasKey('error', $data);
    $this->assertIsArray($data['result']);
    $this->assertCount(3, $data['result']);
    // Check that all three content types are present.
    $types = array_column($data['result'], 'id');
    $this->assertContains('article', $types);
    $this->assertContains('page', $types);
    $this->assertContains('blog', $types);
  }

  /**
   * Tests ListArticles with pagination.
   */
  public function testListArticlesWithPagination(): void {
    // Create test articles.
    for ($i = 1; $i <= 5; $i++) {
      $this->createNode([
        'type' => 'article',
        'title' => "Article $i",
        'status' => 1,
      ]);
    }

    $request = [
      'jsonrpc' => '2.0',
      'method' => 'examples.articles.list',
      'params' => ['page' => ['offset' => 0, 'limit' => 3]],
      'id' => 1,
    ];

    $data = $this->postJsonAndDecode('/jsonrpc', $request);

    $this->assertArrayNotHasKey('error', $data);
    $this->assertArrayHasKey('result', $data);
    $this->assertIsArray($data['result']);
    $this->assertCount(3, $data['result']);
  }

  /**
   * Tests ListArticles without pagination parameters.
   */
  public function testListArticlesWithoutPagination(): void {
    // Create test articles.
    $this->createNode([
      'type' => 'article',
      'title' => 'Article 1',
      'status' => 1,
    ]);
    $this->createNode([
      'type' => 'article',
      'title' => 'Article 2',
      'status' => 1,
    ]);

    $request = [
      'jsonrpc' => '2.0',
      'method' => 'examples.articles.list',
      'params' => [],
      'id' => 1,
    ];

    $data = $this->postJsonAndDecode('/jsonrpc', $request);

    $this->assertArrayNotHasKey('error', $data);
    $this->assertArrayHasKey('result', $data);
    $this->assertIsArray($data['result']);
    $this->assertCount(2, $data['result']);
  }

  /**
   * Tests ListArticles filters unpublished nodes.
   */
  public function testListArticlesFilterUnpublished(): void {
    // Create published and unpublished articles.
    $this->createNode([
      'type' => 'article',
      'title' => 'Published',
      'status' => 1,
    ]);
    $this->createNode([
      'type' => 'article',
      'title' => 'Unpublished',
      'status' => 0,
    ]);

    $request = [
      'jsonrpc' => '2.0',
      'method' => 'examples.articles.list',
      'params' => [],
      'id' => 1,
    ];

    $data = $this->postJsonAndDecode('/jsonrpc', $request);

    $this->assertArrayNotHasKey('error', $data);
    $this->assertIsArray($data['result']);
    $this->assertCount(1, $data['result']);
    $this->assertSame('Published', $data['result'][0]['title']);
  }

  /**
   * Tests ListArticles with no articles.
   */
  public function testListArticlesEmpty(): void {
    $request = [
      'jsonrpc' => '2.0',
      'method' => 'examples.articles.list',
      'params' => [],
      'id' => 1,
    ];

    $data = $this->postJsonAndDecode('/jsonrpc', $request);

    $this->assertArrayNotHasKey('error', $data);
    $this->assertArrayHasKey('result', $data);
    $this->assertIsArray($data['result']);
    $this->assertCount(0, $data['result']);
  }

  /**
   * Tests ListArticles ordering (most recent first).
   */
  public function testListArticlesOrder(): void {
    // Create articles with different creation times.
    $this->createNode([
      'type' => 'article',
      'title' => 'Old Article',
      'status' => 1,
      'created' => time() - 3600,
    ]);
    $this->createNode([
      'type' => 'article',
      'title' => 'New Article',
      'status' => 1,
      'created' => time(),
    ]);

    $request = [
      'jsonrpc' => '2.0',
      'method' => 'examples.articles.list',
      'params' => [],
      'id' => 1,
    ];

    $data = $this->postJsonAndDecode('/jsonrpc', $request);

    $this->assertArrayNotHasKey('error', $data);
    $this->assertIsArray($data['result']);
    $this->assertCount(2, $data['result']);
    // Most recent should be first.
    $this->assertSame('New Article', $data['result'][0]['title']);
    $this->assertSame('Old Article', $data['result'][1]['title']);
  }

  /**
   * Posts JSON data to a path and returns the raw response.
   *
   * @param string $path
   *   The path to post to.
   * @param array $data
   *   The data to post as JSON.
   *
   * @return string
   *   The response body.
   */
  protected function postJson(string $path, array $data): string {
    // Use BrowserTestBase's HTTP client with Basic Auth.
    $client = $this->getHttpClient();

    // passRaw is added by drupalCreateUser() but not in UserInterface.
    /** @var object{passRaw: string} $user */
    $user = $this->testUser;

    $response = $client->request('POST', $this->buildUrl($path), [
      'headers' => [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
      ],
      'body' => json_encode($data),
      'http_errors' => FALSE,
      'auth' => [$this->testUser->getAccountName(), $user->passRaw],
    ]);

    $body = (string) $response->getBody();

    // Debug empty responses.
    if (empty($body)) {
      $this->fail(sprintf(
        'Empty response. Status: %d, Headers: %s',
        $response->getStatusCode(),
        json_encode($response->getHeaders())
      ));
    }

    return $body;
  }

  /**
   * Posts JSON and decodes the response with error handling.
   *
   * @param string $path
   *   The path to post to.
   * @param array $data
   *   The data to post.
   *
   * @return array
   *   The decoded JSON response.
   */
  protected function postJsonAndDecode(string $path, array $data): array {
    $response = $this->postJson($path, $data);

    // Check if response is empty.
    if (empty($response)) {
      $this->fail('Empty response from JSON-RPC endpoint');
    }

    $decoded = Json::decode($response);

    // Provide helpful debugging if JSON decode fails.
    if ($decoded === NULL) {
      $this->fail(sprintf(
        'JSON decode failed. Response (first 1000 chars): %s',
        substr($response, 0, 1000)
      ));
    }

    return $decoded;
  }

}
