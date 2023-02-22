<?php

declare(strict_types=1);

namespace PhpCfdi\Credentials\Pfx;

use PhpCfdi\Credentials\Credential;
use PhpCfdi\Credentials\Internal\LocalFileOpenTrait;

class PfxExporter
{
    use LocalFileOpenTrait;

    private Credential $credential;

    public function __construct(Credential $credential)
    {
        $this->credential = $credential;
    }

    public function getCredential(): Credential
    {
        return $this->credential;
    }

    public function export(string $passPhrase): string
    {
        $pfxContents = '';
        openssl_pkcs12_export(
            $this->credential->certificate()->pem(),
            $pfxContents,
            [$this->credential->privateKey()->pem(), $this->credential->privateKey()->passPhrase()],
            $passPhrase,
        );
        return $pfxContents;
    }

    public function exportToFile(string $pfxFile, string $passPhrase): bool
    {
        return openssl_pkcs12_export_to_file(
            $this->credential->certificate()->pem(),
            $pfxFile,
            [$this->credential->privateKey()->pem(), $this->credential->privateKey()->passPhrase()],
            $passPhrase
        );
    }
}
