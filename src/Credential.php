<?php

declare(strict_types=1);

namespace PhpCfdi\Credentials;

use UnexpectedValueException;

class Credential
{
    /** @var Certificate */
    private $certificate;

    /** @var PrivateKey */
    private $privateKey;

    public function __construct(Certificate $certificate, PrivateKey $privateKey)
    {
        if (! $privateKey->belongsTo($certificate)) {
            throw new UnexpectedValueException('Certificate does not belong to private key');
        }
        $this->certificate = $certificate;
        $this->privateKey = $privateKey;
    }

    public static function openFiles(string $certificateFile, string $privateKeyFile, string $passPhrase): self
    {
        $certificate = Certificate::openFile($certificateFile);
        $privateKey = PrivateKey::openFile($privateKeyFile, $passPhrase);
        return new self($certificate, $privateKey);
    }

    public function certificate(): Certificate
    {
        return $this->certificate;
    }

    public function privateKey(): PrivateKey
    {
        return $this->privateKey;
    }
}
