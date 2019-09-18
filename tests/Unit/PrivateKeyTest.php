<?php

declare(strict_types=1);

namespace PhpCfdi\Credentials\Tests\Unit;

use PhpCfdi\Credentials\Certificate;
use PhpCfdi\Credentials\PrivateKey;
use PhpCfdi\Credentials\Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;

class PrivateKeyTest extends TestCase
{
    public function createPrivateKey(): PrivateKey
    {
        $password = trim($this->fileContents('FIEL_AAA010101AAA/password.txt'));
        $filename = $this->filePath('FIEL_AAA010101AAA/private_key_protected.key.pem');
        return PrivateKey::openFile($filename, $password);
    }

    public function testPemAndPassPhraseProperties(): void
    {
        $passPhrase = trim($this->fileContents('FIEL_AAA010101AAA/password.txt'));
        $fileContents = $this->fileContents('FIEL_AAA010101AAA/private_key_protected.key.pem');
        $privateKey = new PrivateKey($fileContents, $passPhrase);
        $this->assertStringContainsString($privateKey->pem(), $fileContents);
        $this->assertStringStartsWith('-----BEGIN RSA PRIVATE KEY-----', $privateKey->pem());
        $this->assertStringEndsWith('-----END RSA PRIVATE KEY-----', $privateKey->pem());
        $this->assertSame($passPhrase, $privateKey->passPhrase());
    }

    public function testPublicKeyIsTheSameAsInCertificate(): void
    {
        $cerfile = $this->filePath('FIEL_AAA010101AAA/certificate.cer');
        $certificate = Certificate::openFile($cerfile);
        $privateKey = $this->createPrivateKey();
        $publicKey = $privateKey->publicKey();
        $this->assertEquals($certificate->publicKey(), $publicKey);
        $this->assertSame(
            $publicKey,
            $privateKey->publicKey(),
            'publicKey() must return the same instance'
        );
    }

    /**
     * @covers \PhpCfdi\Credentials\PrivateKey
     */
    public function testSign(): void
    {
        $privateKey = $this->createPrivateKey();
        $sourceString = 'the quick brown fox jumps over the lazy dog';
        $signature = $privateKey->sign($sourceString, OPENSSL_ALGO_SHA512);
        $this->assertNotEmpty($signature);

        $publicKey = $privateKey->publicKey();
        $publicKey->verify($sourceString, $signature, OPENSSL_ALGO_SHA512);
    }

    public function testSignCallOpenSslReturnFalse(): void
    {
        $source = $this->fileContents('CSD01_AAA010101AAA/private_key.key.pem');
        /** @var PrivateKey&MockObject $privateKey */
        $privateKey = $this->getMockBuilder(PrivateKey::class)
            ->onlyMethods(['openSslSign'])
            ->setConstructorArgs([$source, ''])
            ->getMock();
        $privateKey->expects($this->once())->method('openSslSign')->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot sign data');
        $privateKey->sign('');
    }

    public function testSignCallOpenSslDontSetSignature(): void
    {
        $source = $this->fileContents('CSD01_AAA010101AAA/private_key.key.pem');
        /** @var PrivateKey&MockObject $privateKey */
        $privateKey = $this->getMockBuilder(PrivateKey::class)
            ->onlyMethods(['openSslSign'])
            ->setConstructorArgs([$source, ''])
            ->getMock();
        $privateKey->expects($this->once())->method('openSslSign')->willReturnCallback(
            function (string $data, ? string &$signature, $privateKey, int $algorithm): bool {
                unset($data, $privateKey, $algorithm); // avoid ugly PhpStorm warning
                $signature = '';
                return true;
            }
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot sign data: empty signature');
        $privateKey->sign('');
    }

    /**
     * @param string $filename
     * @param bool $expectBelongsTo
     * @covers \PhpCfdi\Credentials\PrivateKey::belongsTo
     * @testWith ["FIEL_AAA010101AAA/certificate.cer", true]
     *           ["CSD01_AAA010101AAA/certificate.cer", false]
     */
    public function testBelongsTo(string $filename, bool $expectBelongsTo): void
    {
        $certificate = Certificate::openFile($this->filePath($filename));
        $privateKey = $this->createPrivateKey();
        $this->assertSame($expectBelongsTo, $privateKey->belongsTo($certificate));
    }
}
