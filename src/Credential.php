<?php

declare(strict_types=1);

namespace PhpCfdi\Credentials;

use UnexpectedValueException;

class Credential
{
    private readonly Certificate $certificate;

    private readonly PrivateKey $privateKey;

    /**
     * Credential constructor
     *
     * @throws UnexpectedValueException Certificate does not belong to private key
     */
    public function __construct(Certificate $certificate, PrivateKey $privateKey)
    {
        if (! $privateKey->belongsTo($certificate)) {
            throw new UnexpectedValueException('Certificate does not belong to private key');
        }
        $this->certificate = $certificate;
        $this->privateKey = $privateKey;
    }

    /**
     * Create a Credential object based on string contents
     *
     * The certificate content can be X.509 PEM, X.509 DER or X.509 DER base64
     * The private key content can be PKCS#8 DER, PKCS#8 PEM or PKCS#5 PEM
     */
    public static function create(string $certificateContents, string $privateKeyContents, string $passPhrase): self
    {
        $certificate = new Certificate($certificateContents);
        $privateKey = new PrivateKey($privateKeyContents, $passPhrase);
        return new self($certificate, $privateKey);
    }

    /**
     * Create a Credential object based on local files
     *
     * File paths must be local, can have no schema or file:// schema
     * The certificate file content can be X.509 PEM, X.509 DER or X.509 DER base64
     * The private key file content can be PKCS#8 DER, PKCS#8 PEM or PKCS#5 PEM
     */
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
