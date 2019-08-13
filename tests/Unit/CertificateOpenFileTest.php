<?php

declare(strict_types=1);

namespace PhpCfdi\Credentials\Tests\Unit;

use PhpCfdi\Credentials\Certificate;
use PhpCfdi\Credentials\Tests\TestCase;

class CertificateOpenFileTest extends TestCase
{
    public function testOpenFileWithPemContents(): void
    {
        $filename = $this->filePath('FIEL_AAA010101AAA/certificate.cer.pem');
        $certificate = Certificate::openFile($filename);
        $this->assertSame('30001000000300023685', $certificate->serialNumber()->bytes());
    }

    public function testOpenFileWithDerContents(): void
    {
        $filename = $this->filePath('FIEL_AAA010101AAA/certificate.cer');
        $certificate = Certificate::openFile($filename);
        $this->assertSame('30001000000300023685', $certificate->serialNumber()->bytes());
    }
}
