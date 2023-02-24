<?php

declare(strict_types=1);

namespace PhpCfdi\Credentials\Tests\Unit\Pfx;

use PhpCfdi\Credentials\Credential;
use PhpCfdi\Credentials\Pfx\PfxReader;
use PhpCfdi\Credentials\Tests\TestCase;
use UnexpectedValueException;

class PfxReaderTest extends TestCase
{
    private function obtainKnownCredential(): Credential
    {
        $reader = new PfxReader();
        return $reader->createCredentialFromFile(
            $this->filePath('CSD01_AAA010101AAA/credential_unprotected.pfx'),
            ''
        );
    }

    /**
     * @testWith ["CSD01_AAA010101AAA/credential_unprotected.pfx", ""]
     *           ["CSD01_AAA010101AAA/credential_protected.pfx", "CSD01_AAA010101AAA/password.txt"]
     */
    public function testCreateCredentialFromFile(string $dir, string $passPhrasePath): void
    {
        $passPhrase = $this->fileContents($passPhrasePath);
        $reader = new PfxReader();
        $expectedCsd = $this->obtainKnownCredential();

        $csd = $reader->createCredentialFromFile($this->filePath($dir), $passPhrase);

        $this->assertInstanceOf(Credential::class, $csd);
        $this->assertSame($expectedCsd->certificate()->pem(), $csd->certificate()->pem());
        $this->assertSame($expectedCsd->privateKey()->pem(), $csd->privateKey()->pem());
    }

    public function testCreateCredentialEmptyContents(): void
    {
        $reader = new PfxReader();

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Cannot create credential from empty PFX contents');

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

        $reader->createCredentialFromFile(
            $this->filePath('CSD01_AAA010101AAA/credential_protected.pfx'),
            'wrong-password'
        );
    }
}
