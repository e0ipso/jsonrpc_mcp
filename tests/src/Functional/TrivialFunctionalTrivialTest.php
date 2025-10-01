<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonrpc_mcp\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Trivial functional test to ensure functional test infrastructure works.
 *
 * @group jsonrpc_mcp
 */
class TrivialFunctionalTrivialTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['jsonrpc_mcp'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that functional tests can run.
   */
  public function testTrivial(): void {
    $this->assertEquals('trivial', strtolower('TRIVIAL'));
  }

}
