<?php

declare(strict_types=1);

namespace PhpCfdi\Credentials\Tests\Unit;

use PhpCfdi\Credentials\PrivateKey;
use PhpCfdi\Credentials\PublicKey;
use PhpCfdi\Credentials\Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;

class PublicKeyTest extends TestCase
{
    public function testCreatePublicKeyFromCertificate(): void
    {
        $contents = $this->fileContents('FIEL_AAA010101AAA/certificate.cer.pem');
        $publicKey = new PublicKey($contents);
        $this->assertGreaterThan(0, $publicKey->numberOfBits());
    }

    public function testOpenFile(): void
    {
        $publicKey = PublicKey::openFile($this->filePath('CSD01_AAA010101AAA/public_key.pem'));
        $this->assertGreaterThan(0, $publicKey->numberOfBits());
    }

    public function testCreatePublicKeyWithInvalidData(): void
    {
        $contents = 'invalid data';
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot open public key');
        new PublicKey($contents);
    }

    /**
     * @covers \PhpCfdi\Credentials\PublicKey
     */
    public function testVerify(): void
    {
        $privateKey = PrivateKey::openFile($this->filePath('CSD01_AAA010101AAA/private_key.key.pem'), '');
        $sourceString = 'The quick brown fox jumps over the lazy dog';
        $signature = $privateKey->sign($sourceString);
        $this->assertNotEmpty($signature, 'Private key did not create the signature');

        $publicKey = PublicKey::openFile($this->filePath('CSD01_AAA010101AAA/public_key.pem'));

        $this->assertTrue($publicKey->verify($sourceString, $signature));

        $this->assertFalse(
            $publicKey->verify($sourceString . PHP_EOL, $signature),
            'Signature verification must fail with different source'
        );
        $this->assertFalse(
            $publicKey->verify($sourceString, $signature, OPENSSL_ALGO_SHA512),
            'Signature verification must fail with different algorithm'
        );

        $publicKey->verify($sourceString, '', OPENSSL_ALGO_SHA512);
    }

    public function testVerifyWithError(): void
    {
        $source = $this->fileContents('CSD01_AAA010101AAA/public_key.pem');
        /** @var PublicKey&MockObject $publicKey */
        $publicKey = $this->getMockBuilder(PublicKey::class)
            ->onlyMethods(['openSslVerify'])
            ->setConstructorArgs([$source])
            ->getMock();
        $publicKey->expects($this->once())->method('openSslVerify')->willReturn(-1);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Verify error');
        $publicKey->verify('', '', OPENSSL_ALGO_SHA512);
    }
}
