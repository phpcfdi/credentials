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
    /**
     * @param string $hexadecimal Hexadecimal representation
     */
    public function __construct(private string $hexadecimal)
    {
        if ('' === $this->hexadecimal) {
            throw new UnexpectedValueException('The hexadecimal string is empty');
        }
        if (0 === strcasecmp('0x', substr($this->hexadecimal, 0, 2))) {
            $this->hexadecimal = substr($this->hexadecimal, 2);
        }
        $this->hexadecimal = strtoupper($this->hexadecimal);
        if (! preg_match('/^[0-9A-F]*$/', $this->hexadecimal)) {
            throw new UnexpectedValueException('The hexadecimal string contains invalid characters');
        }
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
        return new self(bin2hex($input));
    }

    public function hexadecimal(): string
    {
        return $this->hexadecimal;
    }

    public function bytes(): string
    {
        return (string) hex2bin($this->hexadecimal);
    }

    public function decimal(): string
    {
        return BaseConverter::createBase36()->convert($this->hexadecimal(), 16, 10);
    }

    public function bytesArePrintable(): bool
    {
        return (bool) preg_match('/^[[:print:]]*$/', $this->bytes());
    }
}
