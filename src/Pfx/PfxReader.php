<?php

declare(strict_types=1);

namespace PhpCfdi\Credentials\Pfx;

use PhpCfdi\Credentials\Credential;
use PhpCfdi\Credentials\Internal\LocalFileOpenTrait;
use UnexpectedValueException;

class PfxReader
{
    use LocalFileOpenTrait;

    public function createCredentialFromContents(string $contents, string $passPhrase): Credential
    {
        if ('' === $contents) {
            throw new UnexpectedValueException('Cannot create credential from empty PFX contents');
        }
        $pfx = $this->loadPkcs12($contents, $passPhrase);
        $certificatePem = $pfx['cert'];
        $privateKeyPem = $pfx['pkey'];
        return Credential::create($certificatePem, $privateKeyPem, '');
    }

    public function createCredentialFromFile(string $fileName, string $passPhrase): Credential
    {
        return $this->createCredentialFromContents(self::localFileOpen($fileName), $passPhrase);
    }

    /**
     * @return array{cert:string, pkey:string}
     */
    public function loadPkcs12(string $contents, string $password = ''): array
    {
        $pfx = [];
        if (! openssl_pkcs12_read($contents, $pfx, $password)) {
            throw new UnexpectedValueException('Invalid PKCS#12 contents or wrong passphrase');
        }
        $certificate = '';
        $privateKey = '';
        if (is_array($pfx)) {
            $certificate = $pfx['cert'] ?? null;
            $privateKey = $pfx['pkey'] ?? null;
        }
        return [
            'cert' => is_string($certificate) ? $certificate : '',
            'pkey' => is_string($privateKey) ? $privateKey : '',
        ];
    }
}
