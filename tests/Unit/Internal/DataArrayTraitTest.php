<?php

declare(strict_types=1);

namespace PhpCfdi\Credentials\Tests\Unit\Internal;

use DateTimeImmutable;
use PhpCfdi\Credentials\Tests\TestCase;

class DataArrayTraitTest extends TestCase
{
    protected function newSpecimen(): DataArrayTraitSpecimen
    {
        return new DataArrayTraitSpecimen([
            'string' => 'bar',
            'int' => 1,
            'date' => 1547388916,
            'array' => [
                'foo' => 'bar',
            ],
        ]);
    }

    public function testExtractString(): void
    {
        $specimen = $this->newSpecimen();
        $this->assertSame('bar', $specimen->extractString('string'));
        $this->assertSame('1', $specimen->extractString('int'));
        $this->assertSame('1547388916', $specimen->extractString('date'));
        $this->assertSame('', $specimen->extractString('nothing'));
        $this->assertSame('', $specimen->extractString('array'));
    }

    public function testExtractInteger(): void
    {
        $specimen = $this->newSpecimen();
        $this->assertSame(0, $specimen->extractInteger('string'));
        $this->assertSame(1, $specimen->extractInteger('int'));
        $this->assertSame(1547388916, $specimen->extractInteger('date'));
        $this->assertSame(0, $specimen->extractInteger('nothing'));
        $this->assertSame(0, $specimen->extractInteger('array'));
    }

    public function testExtractDate(): void
    {
        $specimen = $this->newSpecimen();
        $zero = new DateTimeImmutable('@0');
        $this->assertEquals(new DateTimeImmutable('2019-01-13T14:15:16Z'), $specimen->extractDateTime('date'));
        $this->assertEquals($zero, $specimen->extractDateTime('string'));
        $this->assertEquals($zero->modify('+1 second'), $specimen->extractDateTime('int'));
        $this->assertEquals($zero, $specimen->extractDateTime('nothing'));
        $this->assertEquals($zero, $specimen->extractDateTime('array'));
    }

    public function testExtractArray(): void
    {
        $specimen = $this->newSpecimen();
        $this->assertSame(['foo' => 'bar'], $specimen->extractArray('array'));
        $this->assertSame([], $specimen->extractArray('date'));
        $this->assertSame([], $specimen->extractArray('nothing'));
    }
}
