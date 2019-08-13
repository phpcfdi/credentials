<?php

declare(strict_types=1);

namespace PhpCfdi\Credentials\Tests\Unit;

use PhpCfdi\Credentials\Certificate;
use PhpCfdi\Credentials\Credential;
use PhpCfdi\Credentials\PrivateKey;
use PhpCfdi\Credentials\Tests\TestCase;
use UnexpectedValueException;

class CredentialTest extends TestCase
{
    public function testCreateWithMatchingValues(): void
    {
        $certificate = Certificate::openFile($this->filePath('FIEL_AAA010101AAA/certificate.cer'));
        $privateKey = PrivateKey::openFile($this->filePath('FIEL_AAA010101AAA/private_key.key.pem'), '');
        $fiel = new Credential($certificate, $privateKey);
        $this->assertSame($fiel->certificate(), $certificate);
        $this->assertSame($fiel->privateKey(), $privateKey);
    }

    public function testCreateWithUnmatchedValues(): void
    {
        $certificate = Certificate::openFile($this->filePath('CSD01_AAA010101AAA/certificate.cer'));
        $privateKey = PrivateKey::openFile($this->filePath('FIEL_AAA010101AAA/private_key.key.pem'), '');
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Certificate does not belong to private key');
        new Credential($certificate, $privateKey);
    }

    public function testCreateWithFiles(): void
    {
        $fiel = Credential::openFiles(
            $this->filePath('FIEL_AAA010101AAA/certificate.cer'),
            $this->filePath('FIEL_AAA010101AAA/private_key_protected.key.pem'),
            trim($this->fileContents('FIEL_AAA010101AAA/password.txt'))
        );
        $this->assertTrue($fiel->isFiel());
    }

    public function testCreateCredentialWithContents(): void
    {
        $fiel = Credential::create(
            $this->fileContents('FIEL_AAA010101AAA/certificate.cer'),
            $this->fileContents('FIEL_AAA010101AAA/private_key_protected.key.pem'),
            trim($this->fileContents('FIEL_AAA010101AAA/password.txt'))
        );
        $this->assertTrue($fiel->isFiel());
    }

    public function testShortCuts(): void
    {
        $credential = Credential::openFiles(
            $this->filePath('CSD01_AAA010101AAA/certificate.cer'),
            $this->filePath('CSD01_AAA010101AAA/private_key_protected.key.pem'),
            trim($this->fileContents('CSD01_AAA010101AAA/password.txt'))
        );
        $this->assertTrue($credential->isCsd());
        $this->assertFalse($credential->isFiel());

        $this->assertSame($credential->certificate()->rfc(), $credential->rfc());
        $this->assertSame($credential->certificate()->legalName(), $credential->legalName());

        $textToSign = 'The quick brown fox jumps over the lazy dog';
        $signature = $credential->sign($textToSign);

        $this->assertNotEmpty($signature);
        $this->assertTrue($credential->verify($textToSign, $signature));
    }
}
