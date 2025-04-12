<?php

declare(strict_types=1);

namespace PhpCfdi\Credentials\Tests\Unit\Internal;

use PhpCfdi\Credentials\Internal\BaseConverter;
use PhpCfdi\Credentials\Internal\BaseConverterSequence;
use PhpCfdi\Credentials\Tests\TestCase;
use UnexpectedValueException;

class BaseConverterTest extends TestCase
{
    public function testBasicFunctionality(): void
    {
        $hexSequence = new BaseConverterSequence('0123456789ABCDEF');
        $converter = new BaseConverter($hexSequence);
        $this->assertSame($hexSequence, $converter->sequence());
        $this->assertSame(16, $converter->maximumBase());
        $input = 'FFFF';
        $expected = base_convert($input, 16, 2);
        $this->assertSame($expected, $converter->convert($input, 16, 2));
    }

    public function testConvertEmptyString(): void
    {
        $converter = BaseConverter::createBase36();
        $this->assertSame('0', $converter->convert('', 10, 2));
    }

    /**
     * @testWith [-1]
     *           [0]
     *           [1]
     *           [37]
     */
    public function testInvalidFromBase(int $base): void
    {
        $converter = BaseConverter::createBase36();

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid from base');
        $converter->convert('', $base, 16);
    }

    /**
     * @testWith [-1]
     *           [0]
     *           [1]
     *           [37]
     */
    public function testInvalidToBase(int $base): void
    {
        $converter = BaseConverter::createBase36();

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid to base');
        $converter->convert('', 16, $base);
    }

    public function testConvertWithInputNotIsSequence(): void
    {
        $converter = BaseConverter::createBase36();
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('The number to convert contains invalid characters');
        $converter->convert('@', 16, 10);
    }

    public function testConvertUsingLongInput(): void
    {
        // this is the main reason to exists of BaseConverter class
        // since base_convert cannot handle large inputs
        $input = '3330303031303030303030333030303233373038';
        $expected = '292233162870206001759766198425879490508935868472';
        $converter = BaseConverter::createBase36();
        $this->assertSame($expected, $converter->convert($input, 16, 10));
    }

    public function testConvertZeroUsingSameBase(): void
    {
        $input = '0000000';
        $expected = '0';

        $converter = BaseConverter::createBase36();
        $this->assertSame($expected, $converter->convert($input, 2, 2));
    }

    public function testConvertZeroUsingDifferentBase(): void
    {
        $input = '0000000';
        $expected = '0';

        $converter = BaseConverter::createBase36();
        $this->assertSame($expected, $converter->convert($input, 2, 4));
    }

    public function testConvertZeroUsingLettersSequence(): void
    {
        // base_convert(501020304050607, 8, 16) => 141083105187
        //        501020304050607
        $input = 'FABACADAEAFAGAH';
        //           141083105187
        $expected = 'BEBAIDBAFBIH';

        $converter = new BaseConverter(new BaseConverterSequence('ABCDEFGHIJKLMNOPQRSTUVWXYZ'));
        $this->assertSame($expected, $converter->convert($input, 8, 16));
    }
}
