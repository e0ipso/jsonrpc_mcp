<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonrpc_mcp\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Trivial unit test to ensure unit test infrastructure works.
 *
 * @group jsonrpc_mcp
 */
class TrivialUnitTrivialTest extends TestCase {

  /**
   * Tests that unit tests can run.
   */
  public function testTrivial(): void {
    $this->assertEquals('trivial', strtolower('TRIVIAL'));
  }

}
