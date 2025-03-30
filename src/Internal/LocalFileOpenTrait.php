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
        if (str_starts_with($filename, 'file://')) {
            $filename = substr($filename, 7);
        }

        if ('' === $filename) {
            throw new UnexpectedValueException('The file to open is empty');
        }

        $scheme = strval(parse_url($filename, PHP_URL_SCHEME));
        if ('' !== $scheme && strlen($scheme) > 1) {
            throw new UnexpectedValueException('Invalid scheme to open file');
        }

        $path = (realpath($filename) ?: '');
        if ('' === $path) {
            throw new RuntimeException('Unable to locate the file to open');
        }

        /** @noinspection PhpUsageOfSilenceOperatorInspection */
        $contents = @file_get_contents($path, false) ?: '';
        if ('' === $contents) {
            throw new RuntimeException('File content is empty');
        }

        return $contents;
    }
}
