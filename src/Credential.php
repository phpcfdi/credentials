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

    public static function create(string $certificateContents, string $privateKeyContents, string $passPhrase): self
    {
        $certificate = new Certificate($certificateContents);
        $privateKey = new PrivateKey($privateKeyContents, $passPhrase);
        return new self($certificate, $privateKey);
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

    public function rfc(): string
    {
        return $this->certificate->rfc();
    }

    public function legalName(): string
    {
        return $this->certificate->legalName();
    }

    public function isFiel(): bool
    {
        return $this->certificate()->satType()->isFiel();
    }

    public function isCsd(): bool
    {
        return $this->certificate()->satType()->isCsd();
    }

    public function sign(string $data, int $algorithm = OPENSSL_ALGO_SHA256): string
    {
        return $this->privateKey()->sign($data, $algorithm);
    }

    public function verify(string $data, string $signature, int $algorithm = OPENSSL_ALGO_SHA256): bool
    {
        return $this->certificate()->publicKey()->verify($data, $signature, $algorithm);
    }
}
