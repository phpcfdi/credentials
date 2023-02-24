<?php

declare(strict_types=1);

namespace PhpCfdi\Credentials\Tests\Unit\Pfx;

use PhpCfdi\Credentials\Credential;
use PhpCfdi\Credentials\Pfx\PfxExporter;
use PhpCfdi\Credentials\Pfx\PfxReader;
use PhpCfdi\Credentials\Tests\TestCase;

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

        $created = $pfxExporter->exportToFile($temporaryFile, '');

        $this->assertTrue($created);
        $reader = new PfxReader();
        $this->assertSame(
            $reader->loadPkcs12($this->fileContents('CSD01_AAA010101AAA/credential_unprotected.pfx')),
            $reader->loadPkcs12((string) file_get_contents($temporaryFile))
        );
    }

    public function testExportToFileToInvalidPath(): void
    {
        $credential = $this->createCredential();
        $pfxExporter = new PfxExporter($credential);
        $exportFile = __DIR__ . '/non-existent/path/file.pfx';
        /** @noinspection PhpUsageOfSilenceOperatorInspection */
        $result = @$pfxExporter->exportToFile($exportFile, '');
        $this->assertFalse($result);
    }

    public function testGetCredential(): void
    {
        $credential = $this->createCredential();
        $pfxExporter = new PfxExporter($credential);

        $this->assertSame($credential, $pfxExporter->getCredential());
    }
}
