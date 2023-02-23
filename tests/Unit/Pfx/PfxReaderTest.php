<?php

declare(strict_types=1);

namespace PhpCfdi\Credentials\Tests\Unit\Pfx;

use PhpCfdi\Credentials\Credential;
use PhpCfdi\Credentials\Pfx\PfxReader;
use PhpCfdi\Credentials\Tests\TestCase;
use UnexpectedValueException;

class PfxReaderTest extends TestCase
{
    /**
     * @testWith ["CSD01_AAA010101AAA/certificate.pfx", ""]
     *           ["CSD01_AAA010101AAA/certificate.pfx.pem", ""]
     *           ["CSD01_AAA010101AAA/certificate_pfx_with_pass.pfx", "CSD01_AAA010101AAA/password.txt"]
     *           ["CSD01_AAA010101AAA/certificate_with_pk_with_pass.pfx", ""]
     */
    public function testCreateCredentialFromContents(string $dir, string $passPhrasePath): void
    {
        $passPhrase = $this->fileContents($passPhrasePath);
        $reader = new PfxReader();
        $expectedCsd = $reader->createCredentialFromContents(
            $this->fileContents('CSD01_AAA010101AAA/certificate.pfx'),
            ''
        );

        $csd = $reader->createCredentialFromContents($this->fileContents($dir), $passPhrase);

        $this->assertInstanceOf(Credential::class, $csd);
        $this->assertSame($expectedCsd->certificate()->pem(), $csd->certificate()->pem());
        $this->assertSame($expectedCsd->privateKey()->pem(), $csd->privateKey()->pem());
    }

    public function testCreateCredentialFromPath(): void
    {
        $reader = new PfxReader();

        $csd = $reader->createCredentialFromFile($this->filePath('CSD01_AAA010101AAA/certificate.pfx'), '');

        $this->assertInstanceOf(Credential::class, $csd);
    }

    public function testCreateCredentialEmptyContents(): void
    {
        $reader = new PfxReader();

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Create pfx from empty contents');

        $reader->createCredentialFromContents('', '');
    }

    public function testCreateCredentialWrongContent(): void
    {
        $reader = new PfxReader();

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid PKCS#12 contents or wrong passphrase');

        $reader->createCredentialFromContents('invalid-contents', '');
    }

    public function testCreateCredentialWrongPassword(): void
    {
        $reader = new PfxReader();

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid PKCS#12 contents or wrong passphrase');

        $reader->createCredentialFromContents(
            $this->fileContents('CSD01_AAA010101AAA/certificate_pfx_with_pass.pfx'),
            ''
        );
    }
}
