<?php

declare(strict_types=1);

namespace PhpCfdi\Credentials\Tests\Unit;

use PhpCfdi\Credentials\PemExtractor;
use PhpCfdi\Credentials\Tests\TestCase;

class PemExtractorTest extends TestCase
{
    public function testExtractorWithEmptyContent(): void
    {
        $extractor = new PemExtractor('');
        $this->assertSame('', $extractor->getContents());
        $this->assertSame('', $extractor->extractCertificate());
        $this->assertSame('', $extractor->extractPublicKey());
        $this->assertSame('', $extractor->extractCertificate());
    }

    /** @return array<string, array<string>> */
    public function providerCrLfAndLf(): array
    {
        return [
            'CRLF' => ["\r\n"],
            'LF' => ["\n"],
        ];
    }

    /**
     * @dataProvider providerCrLfAndLf
     */
    public function testExtractorWithFakeContent(string $eol): void
    {
        // section contents must be base64 valid strings
        $info = str_replace(["\r", "\n"], ['[CR]', '[LF]'], $eol);
        $content = implode($eol, [
            '-----BEGIN OTHER SECTION-----',
            'OTHER SECTION',
            '-----END OTHER SECTION-----',
            '-----BEGIN CERTIFICATE-----',
            'FOO+CERTIFICATE',
            '-----END CERTIFICATE-----',
            '-----BEGIN PUBLIC KEY-----',
            'FOO+PUBLIC+KEY',
            '-----END PUBLIC KEY-----',
            '-----BEGIN PRIVATE KEY-----',
            'FOO+PRIVATE+KEY',
            '-----END PRIVATE KEY-----',
        ]);
        $extractor = new PemExtractor($content);
        $this->assertSame($content, $extractor->getContents());
        $this->assertStringContainsString(
            'FOO+CERTIFICATE',
            $extractor->extractCertificate(),
            "Certificate using EOL $info was not extracted"
        );
        $this->assertStringContainsString(
            'FOO+PUBLIC+KEY',
            $extractor->extractPublicKey(),
            "Public Key using EOL $info was not extracted"
        );
        $this->assertStringContainsString(
            'FOO+PRIVATE+KEY',
            $extractor->extractPrivateKey(),
            "Private Key using EOL $info was not extracted"
        );
    }

    public function testExtractCertificateWithPublicKey(): void
    {
        $contents = $this->fileContents('CSD01_AAA010101AAA/certificate_public_key.pem');

        $extractor = new PemExtractor($contents);
        $this->assertSame($contents, $extractor->getContents());

        $this->assertStringContainsString('PUBLIC KEY', $extractor->extractPublicKey());
        $this->assertStringContainsString('CERTIFICATE', $extractor->extractCertificate());
    }

    public function testExtractPrivateKey(): void
    {
        $contents = $this->fileContents('CSD01_AAA010101AAA/private_key.key.pem');

        $extractor = new PemExtractor($contents);
        $this->assertStringContainsString('PRIVATE KEY', $extractor->extractPrivateKey());
    }

    public function testUsingBinaryFileExtractNothing(): void
    {
        $contents = $this->fileContents('CSD01_AAA010101AAA/private_key.key');

        $extractor = new PemExtractor($contents);

        $this->assertSame('', $extractor->extractCertificate());
        $this->assertSame('', $extractor->extractPublicKey());
        $this->assertSame('', $extractor->extractPrivateKey());
    }

    public function testUsingAllInOnePemContents(): void
    {
        $contents = $this->fileContents('CSD01_AAA010101AAA/all_in_one.pem');
        $extractor = new PemExtractor($contents);
        $this->assertStringContainsString('PRIVATE KEY', $extractor->extractPrivateKey());
        $this->assertStringContainsString('CERTIFICATE', $extractor->extractCertificate());
    }

    public function testExtractRsaPrivateKeyWithoutHeaders(): void
    {
        $contents = implode(PHP_EOL, [
            '-----BEGIN RSA PRIVATE KEY-----',
            'FOO+RSA+PRIVATE+KEY',
            '-----END RSA PRIVATE KEY-----',
        ]);
        $extractor = new PemExtractor($contents);
        $this->assertStringContainsString('FOO+RSA+PRIVATE+KEY', $extractor->extractPrivateKey());
    }
}
