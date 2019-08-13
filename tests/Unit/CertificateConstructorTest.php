<?php

declare(strict_types=1);

namespace PhpCfdi\Credentials\Tests\Unit;

use PhpCfdi\Credentials\Certificate;
use PhpCfdi\Credentials\Tests\TestCase;
use UnexpectedValueException;

class CertificateConstructorTest extends TestCase
{
    public function testConstructorWithPemContent(): void
    {
        $pem = $this->fileContents('FIEL_AAA010101AAA/certificate.cer.pem');
        $certificate = new Certificate($pem);
        $this->assertSame('30001000000300023685', $certificate->serialNumber()->bytes());
    }

    public function testConstructorWithDerContent(): void
    {
        $contents = $this->fileContents('FIEL_AAA010101AAA/certificate.cer');
        $certificate = new Certificate($contents);
        $this->assertSame('30001000000300023685', $certificate->serialNumber()->bytes());
    }

    public function testConstructorWithEmptyContent(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Create certificate from empty contents');
        new Certificate('');
    }

    public function testConstructorWithInvalidContent(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Cannot parse X509 certificate from contents');
        new Certificate('x');
    }
}
