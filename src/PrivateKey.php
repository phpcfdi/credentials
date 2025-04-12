<?php

declare(strict_types=1);

namespace PhpCfdi\Credentials;

use Closure;
use OpenSSLAsymmetricKey;
use PhpCfdi\Credentials\Internal\Key;
use PhpCfdi\Credentials\Internal\LocalFileOpenTrait;
use RuntimeException;
use UnexpectedValueException;

class PrivateKey extends Key
{
    use LocalFileOpenTrait;

    /** @var string PEM contents of private key */
    private string $pem;

    private string $passPhrase;

    /** @var PublicKey|null public key extracted from private key */
    private ?PublicKey $publicKey = null;

    /**
     * PrivateKey constructor
     *
     * @param string $source can be a PKCS#8 DER, PKCS#8 PEM or PKCS#5 PEM
     * @param string $passPhrase If empty asume unencrypted/plain private key
     */
    public function __construct(string $source, string $passPhrase)
    {
        if ('' === $source) {
            throw new UnexpectedValueException('Private key is empty');
        }
        $pemExtractor = new PemExtractor($source);
        $pem = $pemExtractor->extractPrivateKey();
        if ('' === $pem) {
            // it could be a DER content, convert to PEM
            $convertSourceIsEncrypted = ('' !== $passPhrase);
            $pem = static::convertDerToPem($source, $convertSourceIsEncrypted);
        }
        $this->pem = $pem;
        $this->passPhrase = $passPhrase;
        $dataArray = $this->callOnPrivateKey(
            fn ($privateKey): array =>
                // no need to verify that openssl_pkey_get_details returns false since it is already open
                openssl_pkey_get_details($privateKey) ?: []
        );
        parent::__construct($dataArray);
    }

    /**
     * Convert PKCS#8 DER to PKCS#8 PEM
     *
     * @param string $contents can be a PKCS#8 DER
     */
    public static function convertDerToPem(string $contents, bool $isEncrypted): string
    {
        $privateKeyName = ($isEncrypted) ? 'ENCRYPTED PRIVATE KEY' : 'PRIVATE KEY';
        return "-----BEGIN $privateKeyName-----" . PHP_EOL
            . chunk_split(base64_encode($contents), 64, PHP_EOL)
            . "-----END $privateKeyName-----";
    }

    /**
     * Create a PrivateKey object by opening a local file
     * The content file can be a PKCS#8 DER, PKCS#8 PEM or PKCS#5 PEM
     *
     * @param string $filename must be a local file (without scheme or file:// scheme)
     */
    public static function openFile(string $filename, string $passPhrase): self
    {
        return new self(self::localFileOpen($filename), $passPhrase);
    }

    public function pem(): string
    {
        return $this->pem;
    }

    public function passPhrase(): string
    {
        return $this->passPhrase;
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
        return $this->callOnPrivateKey(
            function ($privateKey) use ($data, $algorithm): string {
                if (false === $this->openSslSign($data, $signature, $privateKey, $algorithm)) {
                    throw new RuntimeException('Cannot sign data: ' . openssl_error_string());
                }
                $signature = strval($signature);
                if ('' === $signature) {
                    throw new RuntimeException('Cannot sign data: empty signature');
                }
                return $signature;
            }
        );
    }

    /**
     * This method id created to wrap and mock openssl_sign
     *
     * @param OpenSSLAsymmetricKey $privateKey
     * @internal
     */
    protected function openSslSign(string $data, ?string &$signature, $privateKey, int $algorithm): bool
    {
        return openssl_sign($data, $signature, $privateKey, $algorithm); // @phpstan-ignore parameterByRef.type
    }

    public function belongsTo(Certificate $certificate): bool
    {
        return $this->belongsToPEMCertificate($certificate->pem());
    }

    public function belongsToPEMCertificate(string $certificate): bool
    {
        return $this->callOnPrivateKey(
            fn ($privateKey): bool => openssl_x509_check_private_key($certificate, $privateKey)
        );
    }

    /**
     * @template T
     * @param Closure(OpenSSLAsymmetricKey): T $function
     * @return T
     * @throws RuntimeException when cannot open the public key from certificate
     */
    public function callOnPrivateKey(Closure $function)
    {
        /** @var false|OpenSSLAsymmetricKey $privateKey */
        $privateKey = openssl_get_privatekey($this->pem(), $this->passPhrase());
        if (false === $privateKey) {
            throw new RuntimeException('Cannot open private key: ' . openssl_error_string());
        }
        return $function($privateKey);
    }

    /**
     * Export the current private key to a new private key with a different password
     *
     * @param string $newPassPhrase If empty the new private key will be unencrypted
     */
    public function changePassPhrase(string $newPassPhrase): self
    {
        $pem = $this->callOnPrivateKey(
            function ($privateKey) use ($newPassPhrase): string {
                $exportConfig = [
                    'private_key_bits' => $this->publicKey()->numberOfBits(),
                    'encrypt_key' => ('' !== $newPassPhrase), // if empty then set that the key is not encrypted
                ];
                // @codeCoverageIgnoreStart
                if (! openssl_pkey_export($privateKey, $exported, $newPassPhrase, $exportConfig)) {
                    throw new RuntimeException('Cannot export the private KEY to change password');
                }
                if (! is_string($exported) || '' === $exported) {
                    throw new RuntimeException('Exported KEY has not a valid content');
                }
                // @codeCoverageIgnoreEnd
                return $exported;
            }
        );
        return new self($pem, $newPassPhrase);
    }
}
