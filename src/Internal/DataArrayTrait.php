<?php

declare(strict_types=1);

namespace PhpCfdi\Credentials\Internal;

use DateTimeImmutable;

/** @internal */
trait DataArrayTrait
{
    /** @var array content of openssl_x509_parse */
    protected $dataArray;

    protected function extractScalar(string $key, $default)
    {
        $value = $this->dataArray[$key] ?? $default;
        if (is_scalar($value)) {
            return $value;
        }
        return $default;
    }

    protected function extractString(string $key): string
    {
        return strval($this->extractScalar($key, ''));
    }

    protected function extractInteger(string $key): int
    {
        $value = $this->extractScalar($key, 0);
        if (is_numeric($value)) {
            return intval($this->extractScalar($key, 0));
        }
        return 0;
    }

    protected function extractArray(string $key):array
    {
        $data = $this->dataArray[$key] ?? null;
        if (! is_array($data)) {
            return [];
        }
        return $data;
    }

    protected function extractDateTime(string $key): DateTimeImmutable
    {
        return new DateTimeImmutable(sprintf('@%d', $this->extractInteger($key)));
    }
}
