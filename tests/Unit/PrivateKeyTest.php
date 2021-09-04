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

    public function createCertificate(): Certificate
    {
        // this certificate match with PrivateKey returned by createPrivateKey()
        $filename = $this->filePath('FIEL_AAA010101AAA/certificate.cer');
        return Certificate::openFile($filename);
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
            function (string $data, ?string &$signature, $privateKey, int $algorithm): bool {
                unset($data, $privateKey, $algorithm); // avoid ugly PhpStorm warning
                $signature = '';
                return true;
            }
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot sign data: empty signature');
        $privateKey->sign('');
    }

    /** @return array<string, array{string, bool}> */
    public function providerBelongsTo(): array
    {
        return [
            'paired certificate' => ['FIEL_AAA010101AAA/certificate.cer', true],
            'other certificate' => ['CSD01_AAA010101AAA/certificate.cer', false],
        ];
    }

    /**
     * @covers \PhpCfdi\Credentials\PrivateKey::belongsTo
     * @dataProvider providerBelongsTo
     */
    public function testBelongsTo(string $filename, bool $expectBelongsTo): void
    {
        $certificate = Certificate::openFile($this->filePath($filename));
        $privateKey = $this->createPrivateKey();
        $this->assertSame($expectBelongsTo, $privateKey->belongsTo($certificate));
    }

    /** @return array<string, array{string, string}> */
    public function providerChangePassPhrase(): array
    {
        return [
            'clear password' => ['', 'PRIVATE KEY'],
            'change password' => ['other password', 'ENCRYPTED PRIVATE KEY'],
        ];
    }

    /** @dataProvider providerChangePassPhrase */
    public function testChangePassPhrase(string $newPassword, string $expectedHeaderName): void
    {
        $certificate = $this->createCertificate();
        $baseKey = $this->createPrivateKey();

        $changed = $baseKey->changePassPhrase($newPassword);

        $this->assertNotEquals($baseKey->pem(), $changed->pem(), 'Changed PK must be different than base PK');
        $this->assertTrue($changed->belongsTo($certificate), 'Changed PK must belong to certificate');
        $pkcs8Header = sprintf('-----BEGIN %s-----', $expectedHeaderName);
        $this->assertStringStartsWith($pkcs8Header, $changed->pem(), 'Changed PK does not have expected header');
    }
}
