<?php

declare(strict_types=1);

namespace PhpCfdi\Credentials\Tests\Unit;

use PhpCfdi\Credentials\Example;
use PhpCfdi\Credentials\Tests\TestCase;

class ExampleTest extends TestCase
{
    public function testAssertIsworking()
    {
        $example = new Example();
        $this->assertInstanceOf(Example::class, $example);
        $this->markTestSkipped('The unit test environment is working');
    }
}
