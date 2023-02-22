<?php

namespace PhpCfdi\Credentials\Pfx;

use PhpCfdi\Credentials\Credential;
use PhpCfdi\Credentials\Internal\LocalFileOpenTrait;
use PhpCfdi\Credentials\PemExtractor;
use UnexpectedValueException;

class PfxReader
{
    use LocalFileOpenTrait;

    public static function create(string $contents, string $passPhrase): Credential
    {
        if ('' === $contents) {
            throw new UnexpectedValueException('Create pfx from empty contents');
        }
        $pemExtractor = new PemExtractor($contents);
        $certificatePem = $pemExtractor->extractCertificate();
        if ($certificatePem === '') {
            $pfx = static::convertDerToPem($contents, $passPhrase);
            $certificatePem = trim($pfx['cert']);
            $privateKeyPem = trim($pfx['pkey']);
            return Credential::create($certificatePem, $privateKeyPem, '');
        }
        $privateKeyPem = $pemExtractor->extractPrivateKey();
        return Credential::create($certificatePem, $privateKeyPem, '');
    }

    public static function openFile(string $fileName, string $passPhrase): Credential
    {
        return self::create(self::localFileOpen($fileName), $passPhrase);
    }

    /**
     * @return array{cert:string, pkey:string}
     */
    public static function convertDerToPem($contents, $password = ''): array
    {
        $pfx = [];
        openssl_pkcs12_read($contents, $pfx, $password);
        if ($pfx === []) {
            throw new UnexpectedValueException('Wrong Password');
        }
        return $pfx;
    }
}
