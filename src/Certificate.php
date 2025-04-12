<?php

declare(strict_types=1);

namespace PhpCfdi\Credentials;

use DateTimeImmutable;
use PhpCfdi\Credentials\Internal\DataArrayTrait;
use PhpCfdi\Credentials\Internal\LocalFileOpenTrait;
use PhpCfdi\Credentials\Internal\SatTypeEnum;
use UnexpectedValueException;

class Certificate
{
    use DataArrayTrait;
    use LocalFileOpenTrait;

    /** @var string PEM contents including headers */
    private string $pem;

    /** @var string RFC as parsed from subject/x500UniqueIdentifier */
    private string $rfc;

    /** @var string Legal name as parsed from subject/x500UniqueIdentifier */
    private string $legalName;

    /** @var SerialNumber|null Parsed serial number */
    private ?SerialNumber $serialNumber = null;

    /** @var PublicKey|null Parsed public key */
    private ?PublicKey $publicKey = null;

    /**
     * Certificate constructor
     *
     * @param string $contents can be a X.509 PEM, X.509 DER or X.509 DER base64
     */
    public function __construct(string $contents)
    {
        if ('' === $contents) {
            throw new UnexpectedValueException('Create certificate from empty contents');
        }
        $pem = (new PemExtractor($contents))->extractCertificate();
        if ('' === $pem) { // it could be a DER content, convert to PEM
            $pem = static::convertDerToPem($contents);
        }

        $parsed = openssl_x509_parse($pem, true);
        if (false === $parsed) {
            throw new UnexpectedValueException('Cannot parse X509 certificate from contents');
        }
        $this->pem = $pem;
        $this->dataArray = $parsed;
        $this->rfc = strval(strstr($this->subjectData('x500UniqueIdentifier') . ' ', ' ', true));
        $this->legalName = $this->subjectData('name');
    }

    /**
     * Convert X.509 DER base64 or X.509 DER to X.509 PEM
     *
     * @param string $contents can be a certificate format X.509 DER or X.509 DER base64
     */
    public static function convertDerToPem(string $contents): string
    {
        // effectively compare that all the content is base64, if it isn't then encode it
        if ($contents !== base64_encode(base64_decode($contents, true) ?: '')) {
            $contents = base64_encode($contents);
        }
        return '-----BEGIN CERTIFICATE-----' . PHP_EOL
            . chunk_split($contents, 64, PHP_EOL)
            . '-----END CERTIFICATE-----';
    }

    /**
     * Create a Certificate object by opening a local file
     * The content file can be a certificate format X.509 PEM, X.509 DER or X.509 DER base64
     *
     * @param string $filename must be a local file (without scheme or file:// scheme)
     */
    public static function openFile(string $filename): self
    {
        return new self(self::localFileOpen($filename));
    }

    public function pem(): string
    {
        return $this->pem;
    }

    public function pemAsOneLine(): string
    {
        return implode('', preg_grep('/^((?!-).)*$/', explode(PHP_EOL, $this->pem())) ?: []);
    }

    /**
     * @return array<mixed>
     */
    public function parsed(): array
    {
        return $this->dataArray;
    }

    public function rfc(): string
    {
        return $this->rfc;
    }

    public function legalName(): string
    {
        return $this->legalName;
    }

    public function branchName(): string
    {
        return $this->subjectData('OU');
    }

    public function name(): string
    {
        return $this->extractString('name');
    }

    /** @return array<string, string> */
    public function subject(): array
    {
        return $this->extractArrayStrings('subject');
    }

    public function subjectData(string $key): string
    {
        return strval($this->subject()[$key] ?? '');
    }

    public function hash(): string
    {
        return $this->extractString('hash');
    }

    /** @return array<string, string> */
    public function issuer(): array
    {
        return $this->extractArrayStrings('issuer');
    }

    public function issuerData(string $string): string
    {
        return strval($this->issuer()[$string] ?? '');
    }

    public function version(): string
    {
        return $this->extractString('version');
    }

    public function serialNumber(): SerialNumber
    {
        if (null === $this->serialNumber) {
            $this->serialNumber = $this->createSerialNumber(
                $this->extractString('serialNumberHex'),
                $this->extractString('serialNumber')
            );
        }
        return $this->serialNumber;
    }

    public function validFrom(): string
    {
        return $this->extractString('validFrom');
    }

    public function validTo(): string
    {
        return $this->extractString('validTo');
    }

    public function validFromDateTime(): DateTimeImmutable
    {
        return $this->extractDateTime('validFrom_time_t');
    }

    public function validToDateTime(): DateTimeImmutable
    {
        return $this->extractDateTime('validTo_time_t');
    }

    public function signatureTypeSN(): string
    {
        return $this->extractString('signatureTypeSN');
    }

    public function signatureTypeLN(): string
    {
        return $this->extractString('signatureTypeLN');
    }

    public function signatureTypeNID(): string
    {
        return $this->extractString('signatureTypeNID');
    }

    /** @return array<mixed> */
    public function purposes(): array
    {
        return $this->extractArray('purposes');
    }

    /** @return array<string, string> */
    public function extensions(): array
    {
        return $this->extractArrayStrings('extensions');
    }

    public function publicKey(): PublicKey
    {
        if (null === $this->publicKey) {
            // The public key can be created from PUBLIC KEY or CERTIFICATE
            $this->publicKey = new PublicKey($this->pem);
        }
        return $this->publicKey;
    }

    public function satType(): SatTypeEnum
    {
        // as of 2019-08-01 is known that only CSD have OU (Organization Unit)
        if ('' === $this->branchName()) {
            return SatTypeEnum::fiel();
        }
        return SatTypeEnum::csd();
    }

    public function validOn(?DateTimeImmutable $datetime = null): bool
    {
        if (null === $datetime) {
            $datetime = new DateTimeImmutable();
        }
        return $datetime >= $this->validFromDateTime() && $datetime <= $this->validToDateTime();
    }

    protected function createSerialNumber(string $hexadecimal, string $decimal): SerialNumber
    {
        if ('' !== $hexadecimal) {
            return SerialNumber::createFromHexadecimal($hexadecimal);
        }
        if ('' !== $decimal) {
            // in some cases openssl report serialNumberHex on serialNumber
            if (0 === strcasecmp('0x', substr($decimal, 0, 2))) {
                return SerialNumber::createFromHexadecimal(substr($decimal, 2));
            }
            return SerialNumber::createFromDecimal($decimal);
        }
        throw new UnexpectedValueException('Certificate does not contain a serial number');
    }

    public function issuerAsRfc4514(): string
    {
        $issuer = $this->issuer();
        return (new Internal\Rfc4514())->escapeArray($issuer);
    }
}
