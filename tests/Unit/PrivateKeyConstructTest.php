<?php

declare(strict_types=1);

namespace PhpCfdi\Credentials\Tests\Unit;

use PhpCfdi\Credentials\PrivateKey;
use PhpCfdi\Credentials\Tests\TestCase;
use RuntimeException;
use UnexpectedValueException;

class PrivateKeyConstructTest extends TestCase
{
    public function testConstructWithValidContent(): void
    {
        $content = $this->fileContents('FIEL_AAA010101AAA/private_key.key.pem');
        $privateKey = new PrivateKey($content, '');
        $this->assertGreaterThan(0, $privateKey->numberOfBits());
    }

    public function testOpenFileUnprotected(): void
    {
        $filename = $this->filePath('FIEL_AAA010101AAA/private_key.key.pem');
        $privateKey = PrivateKey::openFile($filename, '');
        $this->assertGreaterThan(0, $privateKey->numberOfBits());
    }

    public function testOpenFileWithValidPassword(): void
    {
        $password = trim($this->fileContents('FIEL_AAA010101AAA/password.txt'));
        $filename = $this->filePath('FIEL_AAA010101AAA/private_key_protected.key.pem');
        $privateKey = PrivateKey::openFile($filename, $password);
        $this->assertGreaterThan(0, $privateKey->numberOfBits());
    }

    public function testOpenFileWithInvalidPassword(): void
    {
        $filename = $this->filePath('FIEL_AAA010101AAA/private_key_protected.key.pem');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot open private key');
        PrivateKey::openFile($filename, '');
    }

    public function testConstructWithEmptyContent(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Private key is empty');
        new PrivateKey('', '');
    }

    public function testConstructWithInvalidContent(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Private key is not PEM');
        new PrivateKey('invalid content', '');
    }

    public function testConstructWithInvalidButBase64Content(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Private key is not PEM');
        new PrivateKey('INVALID+CONTENT', '');
    }
}
