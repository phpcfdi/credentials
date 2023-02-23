<?php

declare(strict_types=1);

namespace PhpCfdi\Credentials\Pfx;

use PhpCfdi\Credentials\Credential;
use PhpCfdi\Credentials\Internal\LocalFileOpenTrait;
use PhpCfdi\Credentials\PemExtractor;
use UnexpectedValueException;

class PfxReader
{
    use LocalFileOpenTrait;

    public function createCredentialFromContents(string $contents, string $passPhrase): Credential
    {
        if ('' === $contents) {
            throw new UnexpectedValueException('Create pfx from empty contents');
        }
        $pemExtractor = new PemExtractor($contents);
        $certificatePem = $pemExtractor->extractCertificate();
        if ('' === $certificatePem) {
            $pfx = static::loadPkcs12($contents, $passPhrase);
            $certificatePem = trim($pfx['cert']);
            $privateKeyPem = trim($pfx['pkey']);
            return Credential::create($certificatePem, $privateKeyPem, '');
        }
        $privateKeyPem = $pemExtractor->extractPrivateKey();
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
        openssl_pkcs12_read($contents, $pfx, $password);
        if ([] === $pfx) {
            throw new UnexpectedValueException('Invalid PKCS#12 contents or wrong passphrase');
        }
        return [
            'cert' => $pfx['cert'] ?? '',
            'pkey' => $pfx['pkey'] ?? '',
        ];
    }
}
