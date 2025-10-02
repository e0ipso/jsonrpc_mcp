<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonrpc_mcp_examples\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Component\Serialization\Json;
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

    // Enable cookie authentication for JSON-RPC.
    $this->container->get('config.factory')
      ->getEditable('jsonrpc.settings')
      ->set('cookie', TRUE)
      ->save(TRUE);
    \Drupal::service('router.builder')->rebuild();

    // Create and login a user with JSON-RPC and content access permissions.
    $user = $this->drupalCreateUser([
      'use jsonrpc services',
      'access content',
    ]);
    $this->drupalLogin($user);
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
      'params' => ['nid' => $node->id()],
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
    $this->assertStringContainsString('Node', $data['error']['message']);
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
      'params' => ['nid' => $node->id()],
      'id' => 1,
    ];

    $data = $this->postJsonAndDecode('/jsonrpc', $request);

    $this->assertArrayHasKey('error', $data);
    $this->assertStringContainsString('article', $data['error']['message']);
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
      'params' => ['nid' => $node->id()],
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
      'params' => [],
      'id' => 1,
    ];

    $data = $this->postJsonAndDecode('/jsonrpc', $request);

    $this->assertArrayNotHasKey('error', $data);
    $this->assertIsArray($data['result']);
    $this->assertArrayHasKey('article', $data['result']);
    $this->assertSame('Article', $data['result']['article']);
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
      'params' => [],
      'id' => 1,
    ];

    $data = $this->postJsonAndDecode('/jsonrpc', $request);

    $this->assertArrayNotHasKey('error', $data);
    $this->assertIsArray($data['result']);
    $this->assertCount(3, $data['result']);
    $this->assertArrayHasKey('article', $data['result']);
    $this->assertArrayHasKey('page', $data['result']);
    $this->assertArrayHasKey('blog', $data['result']);
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
      'params' => ['limit' => 3],
      'id' => 1,
    ];

    $data = $this->postJsonAndDecode('/jsonrpc', $request);

    $this->assertArrayNotHasKey('error', $data);
    $this->assertArrayHasKey('articles', $data['result']);
    $this->assertCount(3, $data['result']['articles']);
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
    $this->assertArrayHasKey('articles', $data['result']);
    $this->assertCount(2, $data['result']['articles']);
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
    $this->assertCount(1, $data['result']['articles']);
    $this->assertSame('Published', $data['result']['articles'][0]['title']);
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
    $this->assertArrayHasKey('articles', $data['result']);
    $this->assertCount(0, $data['result']['articles']);
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
    $this->assertCount(2, $data['result']['articles']);
    // Most recent should be first.
    $this->assertSame('New Article', $data['result']['articles'][0]['title']);
    $this->assertSame('Old Article', $data['result']['articles'][1]['title']);
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
    // Use BrowserTestBase's HTTP client with session cookies and CSRF token.
    // drupalLogin() was already called in setUp(), so use those cookies.
    $client = $this->getHttpClient();

    // Get CSRF token for cookie authentication.
    $csrf_token_url = \Drupal\Core\Url::fromRoute('system.csrftoken')
      ->setAbsolute()->toString();
    $csrf_response = $client->get($csrf_token_url, [
      'cookies' => $this->getSessionCookies(),
    ]);
    $csrf_token = (string) $csrf_response->getBody();

    $response = $client->request('POST', $this->buildUrl($path), [
      'headers' => [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
        'X-CSRF-Token' => $csrf_token,
      ],
      'body' => json_encode($data),
      'http_errors' => FALSE,
      'cookies' => $this->getSessionCookies(),
    ]);

    return (string) $response->getBody();
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
