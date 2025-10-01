<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonrpc_mcp\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Trivial functional javascript test to ensure JS test infrastructure works.
 *
 * @group jsonrpc_mcp
 */
class TrivialFunctionalJavascriptTrivialTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['jsonrpc_mcp'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that functional javascript tests can run.
   */
  public function testTrivial(): void {
    $this->assertEquals('trivial', strtolower('TRIVIAL'));
  }

}
