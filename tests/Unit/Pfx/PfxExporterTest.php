<?php

declare(strict_types=1);

namespace PhpCfdi\Credentials\Tests\Unit\Pfx;

use PhpCfdi\Credentials\Certificate;
use PhpCfdi\Credentials\Credential;
use PhpCfdi\Credentials\Pfx\PfxExporter;
use PhpCfdi\Credentials\Pfx\PfxReader;
use PhpCfdi\Credentials\PrivateKey;
use PhpCfdi\Credentials\Tests\TestCase;
use RuntimeException;

class PfxExporterTest extends TestCase
{
    /** @var string */
    private $credentialPassphrase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->credentialPassphrase = trim($this->fileContents('CSD01_AAA010101AAA/password.txt'));
    }

    private function createCredential(): Credential
    {
        return Credential::openFiles(
            $this->filePath('CSD01_AAA010101AAA/certificate.cer'),
            $this->filePath('CSD01_AAA010101AAA/private_key.key'),
            $this->credentialPassphrase
        );
    }

    public function testExport(): void
    {
        $credential = $this->createCredential();
        $pfxExporter = new PfxExporter($credential);

        $pfxContents = $pfxExporter->export('');

        $reader = new PfxReader();
        $this->assertSame(
            $reader->loadPkcs12($this->fileContents('CSD01_AAA010101AAA/credential_unprotected.pfx')),
            $reader->loadPkcs12($pfxContents)
        );
    }

    public function testExportToFile(): void
    {
        $credential = $this->createCredential();
        $pfxExporter = new PfxExporter($credential);
        $temporaryFile = tempnam('', '');
        if (false === $temporaryFile) {
            $this->fail('Expected to create a temporary file');
        }

        $pfxExporter->exportToFile($temporaryFile, '');

        $reader = new PfxReader();
        $this->assertSame(
            $reader->loadPkcs12($this->fileContents('CSD01_AAA010101AAA/credential_unprotected.pfx')),
            $reader->loadPkcs12((string) file_get_contents($temporaryFile))
        );
    }

    public function testExportWithError(): void
    {
        // create a credential with an invalid private key to produce error
        $certificate = Certificate::openFile($this->filePath('CSD01_AAA010101AAA/certificate.cer'));
        $privateKey = $this->createMock(PrivateKey::class);
        $privateKey->method('belongsTo')->willReturn(true);
        $privateKey->method('pem')->willReturn('bar');
        $privateKey->method('passPhrase')->willReturn('baz');
        $malformedCredential = new Credential($certificate, $privateKey);

        $pfxExporter = new PfxExporter($malformedCredential);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches(
            '#^Cannot export credential with certificate 30001000000300023708: #'
        );

        $pfxExporter->export('');
    }

    public function testExportToFileWithError(): void
    {
        $credential = $this->createCredential();
        $pfxExporter = new PfxExporter($credential);
        $exportFile = __DIR__ . '/non-existent/path/file.pfx';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches(
            "#^Cannot export credential with certificate 30001000000300023708 to file $exportFile: #"
        );
        $pfxExporter->exportToFile($exportFile, '');
    }

    public function testGetCredential(): void
    {
        $credential = $this->createCredential();
        $pfxExporter = new PfxExporter($credential);

        $this->assertSame($credential, $pfxExporter->getCredential());
    }
}
