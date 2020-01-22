<?php

declare(strict_types=1);

namespace PhpCfdi\Credentials\Tests\Unit;

use DateTimeImmutable;
use PhpCfdi\Credentials\Certificate;
use PhpCfdi\Credentials\SerialNumber;
use PhpCfdi\Credentials\Tests\TestCase;
use UnexpectedValueException;

/**
 * @covers \PhpCfdi\Credentials\Certificate
 */
class CertificateTest extends TestCase
{
    protected function createCertificate(): Certificate
    {
        return new Certificate($this->fileContents('FIEL_AAA010101AAA/certificate.cer'));
    }

    protected function createCertificateSello(): Certificate
    {
        return new Certificate($this->fileContents('CSD01_AAA010101AAA/certificate.cer'));
    }

    public function testPemContents(): void
    {
        $certificate = $this->createCertificateSello();
        $expected = trim($this->fileContents('CSD01_AAA010101AAA/certificate.cer.pem'));
        $this->assertSame($expected, trim($certificate->pem()));
    }

    public function testPemContentsAsOneLine(): void
    {
        $certificate = $this->createCertificateSello();
        $expected = base64_encode($this->fileContents('CSD01_AAA010101AAA/certificate.cer'));
        $this->assertSame($expected, trim($certificate->pemAsOneLine()));
    }

    public function testSerialNumber(): void
    {
        $certificate = $this->createCertificate();
        $serial = $certificate->serialNumber();
        $this->assertSame('30001000000300023685', $serial->bytes());
        $this->assertSame($serial, $certificate->serialNumber(), 'serialNumber() must return same instance');
    }

    public function testValidDates(): void
    {
        $validSince = new DateTimeImmutable('2017-05-16T23:29:17Z');
        $validUntil = new DateTimeImmutable('2021-05-15T23:29:17Z');

        $certificate = $this->createCertificate();

        $this->assertEquals($validSince, $certificate->validFromDateTime());
        $this->assertEquals($validUntil, $certificate->validToDateTime());
        $this->assertFalse($certificate->validOn($validSince->modify('-1 seconds')));
        $this->assertTrue($certificate->validOn($validSince));
        $this->assertTrue($certificate->validOn($validUntil));
        $this->assertFalse($certificate->validOn($validUntil->modify('+1 seconds')));
    }

    public function testValidOnWithoutDate(): void
    {
        $certificate = $this->createCertificate();
        $now = new DateTimeImmutable();
        $expected = ($now <= $certificate->validToDateTime());
        $this->assertSame($expected, $certificate->validOn());
    }

    public function testRfc(): void
    {
        $this->assertSame('AAA010101AAA', $this->createCertificate()->rfc());
    }

    public function testLegalName(): void
    {
        $this->assertSame('ACCEM SERVICIOS EMPRESARIALES SC', $this->createCertificate()->legalName());
    }

    public function testSatTypeEfirma(): void
    {
        $this->assertTrue($this->createCertificate()->satType()->isFiel());
    }

    public function testSatTypeSello(): void
    {
        $this->assertTrue($this->createCertificateSello()->satType()->isCsd());
    }

    public function testIssuerData(): void
    {
        $certificate = $this->createCertificate();
        $this->assertSame('SAT970701NN3', $certificate->issuerData('x500UniqueIdentifier'));
    }

    public function testIssuerAsRfc4514(): void
    {
        $certificate = $this->createCertificate();
        $expected = [
            'CN=A.C. 2 de pruebas(4096)',
            'O=Servicio de Administración Tributaria',
            'OU=Administración de Seguridad de la Información',
            'emailAddress=asisnet@pruebas.sat.gob.mx',
            'street=Av. Hidalgo 77\2c Col. Guerrero', // see how it was encoded
            'postalCode=06300',
            'C=MX',
            'ST=Distrito Federal',
            'L=Coyoacán',
            'x500UniqueIdentifier=SAT970701NN3',
            'unstructuredName=Responsable: ACDMA',
        ];
        $this->assertEquals($expected, explode(',', $certificate->issuerAsRfc4514()));
    }

    public function testPublicKey(): void
    {
        $certificate = $this->createCertificate();
        $first = $certificate->publicKey();
        $this->assertSame($first, $certificate->publicKey(), 'publicKey() must return same instance');
    }

    public function testParsed(): void
    {
        $certificate = $this->createCertificate();
        $parsed = $certificate->parsed();
        $this->assertArrayHasKey('name', $parsed);
        $this->assertSame($certificate->name(), $parsed['name'] ?? '');
    }

    public function testName(): void
    {
        $certificate = $this->createCertificate();
        $this->assertStringStartsWith('/CN=', $certificate->name());
    }

    public function testHash(): void
    {
        $certificate = $this->createCertificate();
        $this->assertSame('a97f90a7', $certificate->hash());
    }

    public function testVersion(): void
    {
        $certificate = $this->createCertificate();
        $this->assertSame('2', $certificate->version());
    }

    public function testValidFromTo(): void
    {
        $certificate = $this->createCertificate();
        $this->assertStringMatchesFormat('%dZ', $certificate->validFrom());
        $this->assertStringMatchesFormat('%dZ', $certificate->validTo());
    }

    public function testPurposesIsNotEmpty(): void
    {
        $certificate = $this->createCertificate();
        $this->assertNotEmpty($certificate->purposes());
    }

    public function testExtensionsIsNotEmpty(): void
    {
        $certificate = $this->createCertificate();
        $this->assertNotEmpty($certificate->extensions());
    }

    public function testSignature(): void
    {
        $certificate = $this->createCertificate();
        $this->assertNotEmpty($certificate->signatureTypeSN());
        $this->assertNotEmpty($certificate->signatureTypeLN());
        $this->assertNotEmpty($certificate->signatureTypeNID());
    }

    public function testCreateSerialNumber(): void
    {
        $reflection = new \ReflectionClass(Certificate::class);
        $reflectionMethod = $reflection->getMethod('createSerialNumber');
        $reflectionMethod->setAccessible(true);
        $certificate = $reflection->newInstanceWithoutConstructor();
        $createSerialNumber = function ($hexadecimal, $decimal) use ($certificate, $reflectionMethod): SerialNumber {
            return $reflectionMethod->invoke($certificate, $hexadecimal, $decimal);
        };

        /** @var SerialNumber $serialNumber */
        $serialNumber = $createSerialNumber('0x3330', '');
        $this->assertSame('3330', $serialNumber->hexadecimal());

        $serialNumber = $createSerialNumber('', '0x3330');
        $this->assertSame('3330', $serialNumber->hexadecimal());

        $serialNumber = $createSerialNumber('', '13104');
        $this->assertSame('3330', $serialNumber->hexadecimal());

        try {
            $createSerialNumber('', '');
            $this->fail('Call to createSerialNumber did not throw a UnexpectedValueException');
        } catch (UnexpectedValueException $exception) {
            $this->assertStringContainsString('Certificate does not contain a serial number', $exception->getMessage());
        }
    }
}
