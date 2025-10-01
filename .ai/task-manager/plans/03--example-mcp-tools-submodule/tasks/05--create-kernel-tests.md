---
id: 5
group: 'testing'
dependencies: [2, 3, 4]
status: 'pending'
created: '2025-10-01'
skills: ['drupal-kernel-testing', 'phpunit']
---

# Create Kernel Tests for Example Methods

## Objective

Write comprehensive kernel tests that verify all three example methods execute correctly with valid parameters, enforce access control, and handle error cases appropriately.

## Skills Required

- `drupal-backend`: Drupal testing framework, node creation, user/permission management
- `phpunit`: Test assertions, test structure, mocking

## Acceptance Criteria

- [ ] `ExampleMethodsTest.php` kernel test class created
- [ ] Test covers ArticleToMarkdown: valid execution, invalid node, wrong type, access denied
- [ ] Test covers ListContentTypes: returns expected content types
- [ ] Test covers ListArticles: pagination works, access control enforced
- [ ] Test creates fixture article nodes for testing
- [ ] Test handles authenticated and anonymous user scenarios
- [ ] All tests pass with `vendor/bin/phpunit --group jsonrpc_mcp_examples`

## Technical Requirements

- Test class: `tests/src/Kernel/ExampleMethodsTest.php`
- Namespace: `Drupal\Tests\jsonrpc_mcp_examples\Kernel`
- Modules enabled: `['node', 'field', 'text', 'jsonrpc', 'jsonrpc_mcp_examples']`
- Use `NodeCreationTrait` for creating test content
- Use `UserCreationTrait` for creating test users
- Group annotation: `@group jsonrpc_mcp_examples`

## Input Dependencies

- Task 2: ArticleToMarkdown implementation
- Task 3: ListContentTypes implementation
- Task 4: ListArticles implementation

## Output Artifacts

- `modules/jsonrpc_mcp_examples/tests/src/Kernel/ExampleMethodsTest.php`

<details>
<summary>Implementation Notes</summary>

### Meaningful Test Strategy Guidelines

Your critical mantra for test generation is: "write a few tests, mostly integration".

**Focus on:**

- Custom business logic: Markdown conversion, node filtering
- Critical user workflows: Node access checking, pagination
- Edge cases: Invalid node IDs, wrong content types, access denied
- Integration points: Entity API, access system, pagination factory

**Avoid testing:**

- Drupal's entity system (already tested upstream)
- Basic CRUD operations without custom logic
- Framework features (attributes, plugin discovery)

### Test Class Structure

```php
<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonrpc_mcp_examples\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests for jsonrpc_mcp_examples methods.
 *
 * @group jsonrpc_mcp_examples
 */
class ExampleMethodsTest extends KernelTestBase {

  use NodeCreationTrait;
  use UserCreationTrait;

  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'text',
    'filter',
    'jsonrpc',
    'jsonrpc_mcp',
    'jsonrpc_mcp_examples',
  ];

  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['node', 'filter']);

    // Create article content type
    NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ])->save();

    // Create body field (if not auto-created)
    node_add_body_field(NodeType::load('article'));
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
    ]);

    $user = $this->createUser(['access content']);
    $this->container->get('current_user')->setAccount($user);

    $method = $this->container->get('plugin.manager.jsonrpc.method')
      ->createInstance('examples.article.toMarkdown');

    $params = new \Drupal\jsonrpc\JsonRpcObject\ParameterBag(['nid' => $node->id()]);
    $result = $method->execute($params);

    $this->assertIsString($result);
    $this->assertStringContainsString('# Test Article', $result);
    $this->assertStringContainsString('First paragraph.', $result);
    $this->assertStringContainsString('Second paragraph.', $result);
    // Check for double newline between paragraphs
    $this->assertStringContainsString("First paragraph.\n\nSecond paragraph.", $result);
  }

  /**
   * Tests ArticleToMarkdown with invalid node ID.
   */
  public function testArticleToMarkdownInvalidNode(): void {
    $user = $this->createUser(['access content']);
    $this->container->get('current_user')->setAccount($user);

    $method = $this->container->get('plugin.manager.jsonrpc.method')
      ->createInstance('examples.article.toMarkdown');

    $this->expectException(\Drupal\jsonrpc\Exception\JsonRpcException::class);
    $this->expectExceptionMessage('Node with ID 99999 not found');

    $params = new \Drupal\jsonrpc\JsonRpcObject\ParameterBag(['nid' => 99999]);
    $method->execute($params);
  }

  /**
   * Tests ArticleToMarkdown with wrong content type.
   */
  public function testArticleToMarkdownWrongType(): void {
    // Create page content type
    NodeType::create([
      'type' => 'page',
      'name' => 'Page',
    ])->save();

    $node = $this->createNode([
      'type' => 'page',
      'title' => 'Test Page',
    ]);

    $user = $this->createUser(['access content']);
    $this->container->get('current_user')->setAccount($user);

    $method = $this->container->get('plugin.manager.jsonrpc.method')
      ->createInstance('examples.article.toMarkdown');

    $this->expectException(\Drupal\jsonrpc\Exception\JsonRpcException::class);
    $this->expectExceptionMessageMatches('/not an article/');

    $params = new \Drupal\jsonrpc\JsonRpcObject\ParameterBag(['nid' => $node->id()]);
    $method->execute($params);
  }

  /**
   * Tests ListContentTypes returns article type.
   */
  public function testListContentTypes(): void {
    $user = $this->createUser(['access content']);
    $this->container->get('current_user')->setAccount($user);

    $method = $this->container->get('plugin.manager.jsonrpc.method')
      ->createInstance('examples.contentTypes.list');

    $params = new \Drupal\jsonrpc\JsonRpcObject\ParameterBag([]);
    $result = $method->execute($params);

    $this->assertIsArray($result);
    $this->assertNotEmpty($result);

    // Check that article type is in results
    $articleFound = FALSE;
    foreach ($result as $type) {
      if ($type['id'] === 'article') {
        $articleFound = TRUE;
        $this->assertEquals('Article', $type['label']);
        break;
      }
    }
    $this->assertTrue($articleFound, 'Article content type should be in results');
  }

  /**
   * Tests ListArticles with pagination.
   */
  public function testListArticlesWithPagination(): void {
    // Create multiple article nodes
    for ($i = 1; $i <= 5; $i++) {
      $this->createNode([
        'type' => 'article',
        'title' => "Article $i",
      ]);
    }

    $user = $this->createUser(['access content']);
    $this->container->get('current_user')->setAccount($user);

    $method = $this->container->get('plugin.manager.jsonrpc.method')
      ->createInstance('examples.articles.list');

    // Test with pagination: offset 0, limit 3
    $params = new \Drupal\jsonrpc\JsonRpcObject\ParameterBag([
      'page' => ['offset' => 0, 'limit' => 3],
    ]);
    $result = $method->execute($params);

    $this->assertIsArray($result);
    $this->assertCount(3, $result);

    foreach ($result as $article) {
      $this->assertArrayHasKey('nid', $article);
      $this->assertArrayHasKey('title', $article);
      $this->assertArrayHasKey('created', $article);
      $this->assertIsInt($article['nid']);
      $this->assertIsString($article['title']);
      $this->assertIsInt($article['created']);
    }
  }

  /**
   * Tests ListArticles without pagination returns all articles.
   */
  public function testListArticlesWithoutPagination(): void {
    // Create 3 articles
    for ($i = 1; $i <= 3; $i++) {
      $this->createNode([
        'type' => 'article',
        'title' => "Article $i",
      ]);
    }

    $user = $this->createUser(['access content']);
    $this->container->get('current_user')->setAccount($user);

    $method = $this->container->get('plugin.manager.jsonrpc.method')
      ->createInstance('examples.articles.list');

    $params = new \Drupal\jsonrpc\JsonRpcObject\ParameterBag([]);
    $result = $method->execute($params);

    $this->assertCount(3, $result);
  }

}
```

### Key Testing Points

1. **Fixture Setup**: Create article content type and body field in setUp()
2. **User Context**: Set current user with appropriate permissions for each test
3. **Plugin Manager**: Get method instances via plugin.manager.jsonrpc.method service
4. **ParameterBag**: Wrap parameters in ParameterBag as expected by execute()
5. **Assertions**: Test both successful execution and error conditions
6. **Access Control**: Verify methods respect node access and permissions
7. **Markdown Format**: Verify double newlines between paragraphs

### Running Tests

```bash
# Run all example tests
vendor/bin/phpunit --group jsonrpc_mcp_examples

# Run specific test class
vendor/bin/phpunit modules/jsonrpc_mcp_examples/tests/src/Kernel/ExampleMethodsTest.php

# Run single test method
vendor/bin/phpunit --filter testArticleToMarkdownSuccess
```

</details>
