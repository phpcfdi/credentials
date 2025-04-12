<?php

declare(strict_types=1);

namespace PhpCfdi\Credentials\Internal;

/**
 * This class is created to replace escape strings and arrays according to RFC 4514
 * @see https://www.ietf.org/rfc/rfc4514.txt
 * @internal
 */
class Rfc4514
{
    public const LEAD_CHARS = [' ', '#'];

    public const LEAD_REPLACEMENTS = ['\20', '\22'];

    public const TRAIL_CHARS = [' '];

    public const TRAIL_REPLACEMENTS = ['\20'];

    public const INNER_CHARS = ['\\', '"', '+', ',', ';', '<', '=', '>'];

    public const INNER_REPLACEMENTS = ['\5C', '\22', '\2b', '\2c', '\3b', '\3c', '\3d', '\3e'];

    public function escape(string $subject): string
    {
        $prefix = '';
        $sufix = '';
        $firstChar = substr($subject, 0, 1);
        if (in_array($firstChar, self::LEAD_CHARS, true)) {
            $prefix = str_replace(self::LEAD_CHARS, self::LEAD_REPLACEMENTS, $firstChar);
            $subject = substr($subject, 1);
        }

        $lastChar = substr($subject, -1);
        if (in_array($lastChar, self::TRAIL_CHARS, true)) {
            $sufix = str_replace(self::TRAIL_CHARS, self::TRAIL_REPLACEMENTS, $lastChar);
            $subject = substr($subject, 0, -1);
        }

        return $prefix . str_replace(self::INNER_CHARS, self::INNER_REPLACEMENTS, $subject) . $sufix;
    }

    /**
     * @param array<string, string> $values
     */
    public function escapeArray(array $values): string
    {
        return implode(',', array_map(
            fn (string $name, string $value): string => $this->escape($name) . '=' . $this->escape($value),
            array_keys($values),
            $values
        ));
    }
}
