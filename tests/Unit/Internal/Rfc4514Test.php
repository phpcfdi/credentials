<?php

declare(strict_types=1);

namespace PhpCfdi\Credentials\Tests\Unit\Internal;

use PhpCfdi\Credentials\Internal\Rfc4514;
use PhpCfdi\Credentials\Tests\TestCase;

class Rfc4514Test extends TestCase
{
    /**
     * @dataProvider providerEscape
     */
    public function testEscape(string $subject, string $expected): void
    {
        $object = new Rfc4514();
        $this->assertSame($expected, $object->escape($subject));
    }

    public function testEscapeArray(): void
    {
        $subject = [
            'foo' => 'foo bar',
            '#foo' => '#foo bar',
            'foo ' => 'foo bar ',
            'address' => 'Street #1, Somehere',
        ];
        $expected = implode(',', [
            'foo=foo bar',
            '\22foo=\22foo bar',
            'foo\20=foo bar\20',
            'address=Street #1\2c Somehere',
        ]);

        $object = new Rfc4514();
        $this->assertSame($expected, $object->escapeArray($subject));
    }

    /** @return array<string, array<string>> */
    public static function providerEscape(): array
    {
        return [
            'normal' => ['foo bar', 'foo bar'],
            'with lead space' => [' foo', '\20foo'],
            'with double lead space' => ['  foo', '\20 foo'],
            'with inner space' => ['foo bar', 'foo bar'],
            'with trail space' => ['foo ', 'foo\20'],
            'with lead #' => ['#foo bar', '\22foo bar'],
            'with double lead #' => ['##foo', '\22#foo'],
            'with inner #' => ['foo#bar', 'foo#bar'],
            'with trail #' => ['foo bar#', 'foo bar#'],
            'with =' => ['=foo=bar=', '\3dfoo\3dbar\3d'],
            'complex => [# a=1,b>2,c<3 ]' => ['# a=1,b>2,c<3 ', '\22 a\3d1\2cb\3e2\2cc\3c3\20'],
        ];
    }
}
