<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonrpc_mcp\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Trivial kernel test to ensure kernel test infrastructure works.
 *
 * @group jsonrpc_mcp
 */
class TrivialKernelTrivialTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['jsonrpc_mcp'];

  /**
   * Tests that kernel tests can run.
   */
  public function testTrivial(): void {
    $this->assertEquals('trivial', strtolower('TRIVIAL'));
  }

}
