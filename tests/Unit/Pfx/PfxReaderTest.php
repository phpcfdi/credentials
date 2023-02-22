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
        $expectedCsd = PfxReader::create($this->fileContents('CSD01_AAA010101AAA/certificate.pfx'), '');
        $csd = PfxReader::create($this->fileContents($dir), $passPhrase);
        $this->assertInstanceOf(Credential::class, $csd);
        $this->assertSame($expectedCsd->certificate()->pem(), $csd->certificate()->pem());
        $this->assertSame($expectedCsd->privateKey()->pem(), $csd->privateKey()->pem());
    }

    public function testCreateCredentialFromPath(): void
    {
        $csd = PfxReader::openFile($this->filePath('CSD01_AAA010101AAA/certificate.pfx'), '');
        $this->assertInstanceOf(Credential::class, $csd);
    }

    public function testCreateCredentialEmptyContents(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Create pfx from empty contents');
        PfxReader::create('', '');
    }

    public function testCreateCredentialWrongPassword(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Wrong Password');
        PfxReader::create($this->fileContents('CSD01_AAA010101AAA/certificate_pfx_with_pass.pfx'), '');
    }
}
