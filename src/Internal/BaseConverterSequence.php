<?php

declare(strict_types=1);

namespace PhpCfdi\Credentials\Internal;

use UnexpectedValueException;

/** @internal  */
class BaseConverterSequence implements \Stringable
{
    private readonly string $sequence;

    private readonly int $length;

    public function __construct(string $sequence)
    {
        self::checkIsValid($sequence);

        $this->sequence = $sequence;
        $this->length = strlen($sequence);
    }

    public function __toString(): string
    {
        return $this->sequence;
    }

    public function value(): string
    {
        return $this->sequence;
    }

    public function length(): int
    {
        return $this->length;
    }

    public static function isValid(string $value): bool
    {
        try {
            static::checkIsValid($value);
            return true;
        } catch (UnexpectedValueException) {
            return false;
        }
    }

    public static function checkIsValid(string $sequence): void
    {
        $length = strlen($sequence);

        // is not empty
        if ($length < 2) {
            throw new UnexpectedValueException('Sequence does not contains enough elements');
        }

        if ($length !== mb_strlen($sequence)) {
            throw new UnexpectedValueException('Cannot use multibyte strings in dictionary');
        }

        $valuesCount = array_count_values(str_split(strtoupper($sequence)));
        $repeated = array_filter($valuesCount, fn (int $count): bool => 1 !== $count);
        if ([] !== $repeated) {
            throw new UnexpectedValueException(
                sprintf('The sequence has not unique values: "%s"', implode(', ', array_keys($repeated)))
            );
        }
    }
}
