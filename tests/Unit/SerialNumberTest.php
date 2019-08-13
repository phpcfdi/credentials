<?php

declare(strict_types=1);

namespace PhpCfdi\Credentials\Tests\Unit;

use PhpCfdi\Credentials\SerialNumber;
use PhpCfdi\Credentials\Tests\TestCase;
use UnexpectedValueException;

class SerialNumberTest extends TestCase
{
    public const SERIAL_HEXADECIMAL = '3330303031303030303030333030303233373038';

    public const SERIAL_BYTES = '30001000000300023708';

    public const SERIAL_DECIMAL = '292233162870206001759766198425879490508935868472';

    /**
     * @param string $prefix
     * @testWith [""]
     *           ["0X"]
     *           ["0x"]
     */
    public function testCreateFromHexadecimal(string $prefix): void
    {
        $value = $prefix . self::SERIAL_HEXADECIMAL;
        $serial = SerialNumber::createFromHexadecimal($value);
        $this->assertSame(self::SERIAL_HEXADECIMAL, $serial->hexadecimal());
        $this->assertSame(self::SERIAL_DECIMAL, $serial->decimal());
        $this->assertSame(self::SERIAL_BYTES, $serial->bytes());
    }

    public function testCreateHexadecimalEmpty(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('is empty');
        SerialNumber::createFromHexadecimal('');
    }

    public function testCreateHexadecimalInvalidChars(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('contains invalid characters');
        SerialNumber::createFromHexadecimal('0x001122x3');
    }

    public function testCreateHexadecimalDoublePrefix(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('contains invalid characters');
        SerialNumber::createFromHexadecimal('0x0xFF');
    }

    public function testCreateFromDecimal(): void
    {
        $serial = SerialNumber::createFromDecimal(self::SERIAL_DECIMAL);
        $this->assertSame(self::SERIAL_BYTES, $serial->bytes());
    }

    public function testCreateFromBytes(): void
    {
        $serial = SerialNumber::createFromBytes(self::SERIAL_BYTES);
        $this->assertSame(self::SERIAL_HEXADECIMAL, $serial->hexadecimal());
    }
}
