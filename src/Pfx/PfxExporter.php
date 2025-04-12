<?php

declare(strict_types=1);

namespace PhpCfdi\Credentials\Pfx;

use PhpCfdi\Credentials\Credential;
use PhpCfdi\Credentials\Internal\LocalFileOpenTrait;
use RuntimeException;

class PfxExporter
{
    use LocalFileOpenTrait;

    public function __construct(private Credential $credential)
    {
    }

    public function getCredential(): Credential
    {
        return $this->credential;
    }

    public function export(string $passPhrase): string
    {
        $pfxContents = '';
        /** @noinspection PhpUsageOfSilenceOperatorInspection */
        $success = @openssl_pkcs12_export(
            $this->credential->certificate()->pem(),
            $pfxContents,
            [$this->credential->privateKey()->pem(), $this->credential->privateKey()->passPhrase()],
            $passPhrase,
        );
        if (! $success || ! is_string($pfxContents)) {
            throw $this->exceptionFromLastError(sprintf(
                'Cannot export credential with certificate %s',
                $this->credential->certificate()->serialNumber()->bytes()
            ));
        }
        return $pfxContents;
    }

    public function exportToFile(string $pfxFile, string $passPhrase): void
    {
        /** @noinspection PhpUsageOfSilenceOperatorInspection */
        $success = @openssl_pkcs12_export_to_file(
            $this->credential->certificate()->pem(),
            $pfxFile,
            [$this->credential->privateKey()->pem(), $this->credential->privateKey()->passPhrase()],
            $passPhrase
        );
        if (! $success) {
            throw $this->exceptionFromLastError(sprintf(
                'Cannot export credential with certificate %s to file %s',
                $this->credential->certificate()->serialNumber()->bytes(),
                $pfxFile
            ));
        }
    }

    private function exceptionFromLastError(string $message): RuntimeException
    {
        $previousError = error_get_last() ?? [];
        return new RuntimeException(sprintf('%s: %s', $message, $previousError['message'] ?? '(Unknown reason)'));
    }
}
