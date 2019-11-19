<?php

declare(strict_types=1);

namespace PhpCfdi\Credentials\Internal;

use RuntimeException;
use UnexpectedValueException;

/** @internal */
trait LocalFileOpenTrait
{
    private static function localFileOpen(string $filename): string
    {
        if ('file://' === substr($filename, 0, 7)) {
            $filename = substr($filename, 7);
        }
        $scheme = strval(parse_url($filename, PHP_URL_SCHEME));
        if ('' !== $scheme) {
            throw new UnexpectedValueException('Invalid scheme to open file');
        }

        /** @noinspection PhpUsageOfSilenceOperatorInspection */
        $contents = @file_get_contents($filename, false) ?: '';
        if ('' === $contents) {
            throw new RuntimeException('File content is empty');
        }

        return $contents;
    }
}
