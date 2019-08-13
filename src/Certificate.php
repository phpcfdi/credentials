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
    private $pem;

    /** @var string RFC as parsed from subject/x500UniqueIdentifier */
    private $rfc;

    /** @var string Legal name as parsed from subject/x500UniqueIdentifier */
    private $legalName;

    /** @var SerialNumber|null Parsed serial number */
    private $serialNumber;

    /** @var PublicKey|null Parsed public key */
    private $publicKey;

    public function __construct(string $contents)
    {
        if ('' === $contents) {
            throw new UnexpectedValueException('Create certificate from empty contents');
        }
        $pem = (new PemExtractor($contents))->extractCertificate();
        if ('' === $pem) { // there is no pem certificate, convert from DER
            /** @noinspection RegExpRedundantEscape phpstorm claims "\/" ... you are drunk, go home */
            if (boolval(preg_match('/^[a-zA-Z0-9+\/]+={0,2}$/', $contents))) {
                // if contents are base64 encoded, then decode it
                $contents = base64_decode($contents, true) ?: '';
            }
            $pem = '-----BEGIN CERTIFICATE-----' . PHP_EOL
                . chunk_split(base64_encode($contents), 64, PHP_EOL)
                . '-----END CERTIFICATE-----';
        }

        /** @var array|false $parsed */
        $parsed = openssl_x509_parse($pem, true);
        if (false === $parsed) {
            throw new UnexpectedValueException('Cannot parse X509 certificate from contents');
        }
        $this->pem = $pem;
        $this->dataArray = $parsed;
        $this->rfc = strval(strstr(($parsed['subject']['x500UniqueIdentifier'] ?? '') . ' ', ' ', true));
        $this->legalName = strval($parsed['subject']['name'] ?? '');
    }

    public static function openFile(string $filename)
    {
        return new self(static::localFileOpen($filename));
    }

    public function pem(): string
    {
        return $this->pem;
    }

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

    public function subject(): array
    {
        return $this->extractArray('subject');
    }

    public function subjectData(string $key): string
    {
        return strval($this->subject()[$key] ?? '');
    }

    public function hash(): string
    {
        return $this->extractString('hash');
    }

    public function issuer(): array
    {
        return $this->extractArray('issuer');
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

    public function purposes(): array
    {
        return $this->extractArray('purposes');
    }

    public function extensions(): array
    {
        return $this->extractArray('extensions');
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

    public function validOn(DateTimeImmutable $datetime = null): bool
    {
        if (null === $datetime) {
            $datetime = new DateTimeImmutable();
        }
        return ($datetime >= $this->validFromDateTime() && $datetime <= $this->validToDateTime());
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
