<?php

declare(strict_types=1);

namespace PhpCfdi\Credentials;

use Closure;
use PhpCfdi\Credentials\Internal\Key;
use PhpCfdi\Credentials\Internal\LocalFileOpenTrait;
use RuntimeException;
use UnexpectedValueException;

class PrivateKey extends Key
{
    use LocalFileOpenTrait;

    /** @var string PEM contents of private key*/
    private $key;

    /** @var string */
    private $passPhrase;

    /** @var PublicKey|null $public key extracted from private key */
    private $publicKey;

    public function __construct(string $source, string $passPhrase)
    {
        if ('' === $source) {
            throw new UnexpectedValueException('Private key is empty');
        }
        $pemExtractor = new PemExtractor($source);
        $source = $pemExtractor->extractPrivateKey();
        if ('' === $source) {
            throw new UnexpectedValueException('Private key is not PEM');
        }
        $this->key = $source;
        $this->passPhrase = $passPhrase;
        $dataArray = $this->callOnPrivateKey(
            function ($privateKey): array {
                // no need to verify that openssl_pkey_get_details returns false since it is already open
                return openssl_pkey_get_details($privateKey) ?: [];
            }
        );
        parent::__construct($dataArray);
    }

    public static function openFile(string $filename, string $passPhrase): self
    {
        return new self(static::localFileOpen($filename), $passPhrase);
    }

    public function publicKey(): PublicKey
    {
        if (null === $this->publicKey) {
            $this->publicKey = new PublicKey($this->publicKeyContents());
        }
        return $this->publicKey;
    }

    public function sign(string $data, int $algorithm = OPENSSL_ALGO_SHA256): string
    {
        return (string) $this->callOnPrivateKey(
            function ($privateKey) use ($data, $algorithm) {
                if (false === $this->openSslSign($data, $signature, $privateKey, $algorithm)) {
                    throw new RuntimeException('Cannot sign data: ' . openssl_error_string());
                }
                if ('' === $signature) {
                    throw new RuntimeException('Cannot sign data: empty signature');
                }
                return $signature;
            }
        );
    }

    /**
     * This method id created to wrap and mock openssl_sign
     * @param string $data
     * @param string|null $signature
     * @param resource $privateKey
     * @param int $algorithm
     * @return bool
     * @internal
     */
    protected function openSslSign(string $data, ? string &$signature, $privateKey, int $algorithm): bool
    {
        return openssl_sign($data, $signature, $privateKey, $algorithm);
    }

    public function belongsTo(Certificate $certificate): bool
    {
        return $this->belongsToPEMCertificate($certificate->pem());
    }

    public function belongsToPEMCertificate(string $certificate): bool
    {
        return $this->callOnPrivateKey(
            function ($privateKey) use ($certificate): bool {
                return openssl_x509_check_private_key($certificate, $privateKey);
            }
        );
    }

    /**
     * @param Closure $function
     * @return mixed
     * @throws RuntimeException when cannot open the public key from certificate
     */
    public function callOnPrivateKey(Closure $function)
    {
        $privateKey = openssl_get_privatekey($this->key, $this->passPhrase);
        if (! is_resource($privateKey)) {
            throw new RuntimeException('Cannot open private key: ' . openssl_error_string());
        }
        try {
            return call_user_func($function, $privateKey);
        } finally {
            openssl_free_key($privateKey);
        }
    }
}
