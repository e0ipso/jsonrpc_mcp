<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonrpc_mcp_examples\Functional;

use GuzzleHttp\RequestOptions;
use Drupal\Component\Serialization\Json;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Functional tests for jsonrpc_mcp_examples methods.
 *
 * @group jsonrpc_mcp_examples
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
    'field',
    'text',
    'filter',
    'jsonrpc',
    'jsonrpc_mcp',
    'jsonrpc_mcp_examples',
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
    node_add_body_field(NodeType::load('article'));

    // Grant permission to use JSON-RPC services.
    $this->drupalCreateRole([
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
        'format' => 'basic_html',
      ],
      'status' => 1,
    ]);

    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);

    $request = [
      'jsonrpc' => '2.0',
      'method' => 'examples.article.toMarkdown',
      'params' => ['nid' => (int) $node->id()],
      'id' => '1',
    ];

    $response = $this->postJson('/jsonrpc', $request);
    $data = Json::decode($response);

    $this->assertArrayNotHasKey('error', $data);
    $this->assertArrayHasKey('result', $data);
    $this->assertIsString($data['result']);
    $this->assertStringContainsString('# Test Article', $data['result']);
    $this->assertStringContainsString('First paragraph.', $data['result']);
    $this->assertStringContainsString('Second paragraph.', $data['result']);
    $this->assertStringContainsString("First paragraph.\n\nSecond paragraph.", $data['result']);
  }

  /**
   * Tests ArticleToMarkdown with invalid node ID.
   */
  public function testArticleToMarkdownInvalidNode(): void {
    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);

    $request = [
      'jsonrpc' => '2.0',
      'method' => 'examples.article.toMarkdown',
      'params' => ['nid' => 99999],
      'id' => '1',
    ];

    $response = $this->postJson('/jsonrpc', $request);
    $data = Json::decode($response);

    $this->assertArrayHasKey('error', $data);
    $this->assertStringContainsString('not found', $data['error']['message']);
  }

  /**
   * Tests ArticleToMarkdown with wrong content type.
   */
  public function testArticleToMarkdownWrongType(): void {
    NodeType::create([
      'type' => 'page',
      'name' => 'Page',
    ])->save();

    $node = $this->createNode([
      'type' => 'page',
      'title' => 'Test Page',
      'status' => 1,
    ]);

    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);

    $request = [
      'jsonrpc' => '2.0',
      'method' => 'examples.article.toMarkdown',
      'params' => ['nid' => (int) $node->id()],
      'id' => '1',
    ];

    $response = $this->postJson('/jsonrpc', $request);
    $data = Json::decode($response);

    $this->assertArrayHasKey('error', $data);
    $this->assertStringContainsString('not an article', $data['error']['message']);
  }

  /**
   * Tests ArticleToMarkdown with complex HTML formatting.
   */
  public function testArticleToMarkdownComplexHtml(): void {
    $node = $this->createNode([
      'type' => 'article',
      'title' => 'Complex Article',
      'body' => [
        'value' => '<p>First <strong>bold</strong> paragraph.</p><p>Second <em>italic</em> paragraph.</p><p>Third paragraph with <a href="#">link</a>.</p>',
        'format' => 'basic_html',
      ],
      'status' => 1,
    ]);

    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);

    $request = [
      'jsonrpc' => '2.0',
      'method' => 'examples.article.toMarkdown',
      'params' => ['nid' => (int) $node->id()],
      'id' => '1',
    ];

    $response = $this->postJson('/jsonrpc', $request);
    $data = Json::decode($response);

    $this->assertArrayNotHasKey('error', $data);
    $result = $data['result'];
    $this->assertStringContainsString('First bold paragraph.', $result);
    $this->assertStringContainsString('Second italic paragraph.', $result);
    $this->assertStringContainsString('Third paragraph with link.', $result);
    $this->assertStringNotContainsString('<strong>', $result);
    $this->assertStringNotContainsString('<em>', $result);
    $this->assertStringNotContainsString('<a ', $result);
  }

  /**
   * Tests ListContentTypes returns article type.
   */
  public function testListContentTypes(): void {
    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);

    $request = [
      'jsonrpc' => '2.0',
      'method' => 'examples.contentTypes.list',
      'params' => [],
      'id' => '1',
    ];

    $response = $this->postJson('/jsonrpc', $request);
    $data = Json::decode($response);

    $this->assertArrayNotHasKey('error', $data);
    $this->assertArrayHasKey('result', $data);
    $this->assertIsArray($data['result']);
    $this->assertNotEmpty($data['result']);

    $articleFound = FALSE;
    foreach ($data['result'] as $type) {
      $this->assertArrayHasKey('id', $type);
      $this->assertArrayHasKey('label', $type);
      if ($type['id'] === 'article') {
        $articleFound = TRUE;
        $this->assertEquals('Article', $type['label']);
      }
    }
    $this->assertTrue($articleFound, 'Article content type should be in results');
  }

  /**
   * Tests ListContentTypes with multiple content types.
   */
  public function testListContentTypesMultiple(): void {
    NodeType::create([
      'type' => 'page',
      'name' => 'Basic Page',
    ])->save();

    NodeType::create([
      'type' => 'blog',
      'name' => 'Blog Post',
    ])->save();

    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);

    $request = [
      'jsonrpc' => '2.0',
      'method' => 'examples.contentTypes.list',
      'params' => [],
      'id' => '1',
    ];

    $response = $this->postJson('/jsonrpc', $request);
    $data = Json::decode($response);

    $this->assertArrayNotHasKey('error', $data);
    $this->assertCount(3, $data['result']);

    $types = array_column($data['result'], 'id');
    $this->assertContains('article', $types);
    $this->assertContains('page', $types);
    $this->assertContains('blog', $types);
  }

  /**
   * Tests ListArticles with pagination.
   */
  public function testListArticlesWithPagination(): void {
    for ($i = 1; $i <= 5; $i++) {
      $this->createNode([
        'type' => 'article',
        'title' => "Article $i",
        'status' => 1,
      ]);
    }

    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);

    $request = [
      'jsonrpc' => '2.0',
      'method' => 'examples.articles.list',
      'params' => ['page' => ['offset' => 0, 'limit' => 3]],
      'id' => '1',
    ];

    $response = $this->postJson('/jsonrpc', $request);
    $data = Json::decode($response);

    $this->assertArrayNotHasKey('error', $data);
    $this->assertCount(3, $data['result']);

    foreach ($data['result'] as $article) {
      $this->assertArrayHasKey('nid', $article);
      $this->assertArrayHasKey('title', $article);
      $this->assertArrayHasKey('created', $article);
      $this->assertIsInt($article['nid']);
      $this->assertIsString($article['title']);
      $this->assertIsInt($article['created']);
    }

    // Test second page.
    $request['params']['page'] = ['offset' => 3, 'limit' => 3];
    $response = $this->postJson('/jsonrpc', $request);
    $data = Json::decode($response);

    $this->assertCount(2, $data['result']);
  }

  /**
   * Tests ListArticles without pagination returns all articles.
   */
  public function testListArticlesWithoutPagination(): void {
    for ($i = 1; $i <= 3; $i++) {
      $this->createNode([
        'type' => 'article',
        'title' => "Article $i",
        'status' => 1,
      ]);
    }

    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);

    $request = [
      'jsonrpc' => '2.0',
      'method' => 'examples.articles.list',
      'params' => [],
      'id' => '1',
    ];

    $response = $this->postJson('/jsonrpc', $request);
    $data = Json::decode($response);

    $this->assertArrayNotHasKey('error', $data);
    $this->assertCount(3, $data['result']);
  }

  /**
   * Tests ListArticles filters unpublished nodes.
   */
  public function testListArticlesFilterUnpublished(): void {
    $this->createNode([
      'type' => 'article',
      'title' => 'Published Article',
      'status' => 1,
    ]);

    $this->createNode([
      'type' => 'article',
      'title' => 'Unpublished Article',
      'status' => 0,
    ]);

    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);

    $request = [
      'jsonrpc' => '2.0',
      'method' => 'examples.articles.list',
      'params' => [],
      'id' => '1',
    ];

    $response = $this->postJson('/jsonrpc', $request);
    $data = Json::decode($response);

    $this->assertArrayNotHasKey('error', $data);
    $this->assertCount(1, $data['result']);
    $this->assertEquals('Published Article', $data['result'][0]['title']);
  }

  /**
   * Tests ListArticles returns empty array when no articles exist.
   */
  public function testListArticlesEmpty(): void {
    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);

    $request = [
      'jsonrpc' => '2.0',
      'method' => 'examples.articles.list',
      'params' => [],
      'id' => '1',
    ];

    $response = $this->postJson('/jsonrpc', $request);
    $data = Json::decode($response);

    $this->assertArrayNotHasKey('error', $data);
    $this->assertIsArray($data['result']);
    $this->assertEmpty($data['result']);
  }

  /**
   * Tests ListArticles respects creation order.
   */
  public function testListArticlesOrder(): void {
    $this->createNode([
      'type' => 'article',
      'title' => 'First Article',
      'status' => 1,
      'created' => 1000,
    ]);

    $this->createNode([
      'type' => 'article',
      'title' => 'Second Article',
      'status' => 1,
      'created' => 2000,
    ]);

    $this->createNode([
      'type' => 'article',
      'title' => 'Third Article',
      'status' => 1,
      'created' => 3000,
    ]);

    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);

    $request = [
      'jsonrpc' => '2.0',
      'method' => 'examples.articles.list',
      'params' => [],
      'id' => '1',
    ];

    $response = $this->postJson('/jsonrpc', $request);
    $data = Json::decode($response);

    $this->assertArrayNotHasKey('error', $data);
    $this->assertEquals('Third Article', $data['result'][0]['title']);
    $this->assertEquals('Second Article', $data['result'][1]['title']);
    $this->assertEquals('First Article', $data['result'][2]['title']);
  }

  /**
   * Helper to post JSON-RPC request.
   *
   * @param string $path
   *   The request path.
   * @param array $data
   *   The JSON-RPC request data.
   *
   * @return string
   *   The response body.
   */
  protected function postJson(string $path, array $data): string {
    $url = $this->buildUrl($path);
    $request_options = [
      RequestOptions::HTTP_ERRORS => FALSE,
      RequestOptions::ALLOW_REDIRECTS => FALSE,
      RequestOptions::JSON => $data,
    ];

    $client = $this->getHttpClient();
    $response = $client->request('POST', $url, $request_options);

    return (string) $response->getBody();
  }

}
