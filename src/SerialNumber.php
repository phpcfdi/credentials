<?php

declare(strict_types=1);

namespace PhpCfdi\Credentials;

use PhpCfdi\Credentials\Internal\BaseConverter;
use UnexpectedValueException;

/**
 * This class is used to load hexadecimal or decimal data as a certificate serial number.
 * It has its own class because SOLID and is easy to test in this way.
 * It is not intended to use in general.
 */
class SerialNumber
{
    /** @var string Hexadecimal representation */
    private $hexadecimal;

    public function __construct(string $hexadecimal)
    {
        if ('' === $hexadecimal) {
            throw new UnexpectedValueException('The hexadecimal string is empty');
        }
        if (0 === strcasecmp('0x', substr($hexadecimal, 0, 2))) {
            $hexadecimal = substr($hexadecimal, 2);
        }
        if (! boolval(preg_match('/^[0-9a-f]*$/', $hexadecimal))) {
            throw new UnexpectedValueException('The hexadecimal string contains invalid characters');
        }
        $this->hexadecimal = $hexadecimal;
    }

    public static function createFromHexadecimal(string $hexadecimal): self
    {
        return new self($hexadecimal);
    }

    public static function createFromDecimal(string $decString): self
    {
        $hexadecimal = BaseConverter::createBase36()->convert($decString, 10, 16);
        return new self($hexadecimal);
    }

    public static function createFromBytes(string $input): self
    {
        /** @noinspection PhpRedundantOptionalArgumentInspection */
        $hexadecimal = implode('', array_map(
            function (string $value): string {
                return dechex(ord($value));
            },
            str_split($input, 1)
        ));
        return new self($hexadecimal);
    }

    public function hexadecimal(): string
    {
        return $this->hexadecimal;
    }

    public function bytes(): string
    {
        return implode('', array_map(function (string $value): string {
            return chr(intval(hexdec($value)));
        }, str_split($this->hexadecimal, 2)));
    }

    public function decimal(): string
    {
        return BaseConverter::createBase36()->convert($this->hexadecimal(), 16, 10);
    }
}
