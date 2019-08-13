<?php

declare(strict_types=1);

namespace PhpCfdi\Credentials\Tests\Unit\Internal;

use Eclipxe\Enum\Exceptions\IndexNotFoundException;
use PhpCfdi\Credentials\Internal\Key;
use PhpCfdi\Credentials\Tests\TestCase;

class KeyTest extends TestCase
{
    public function testAccessorsUsingFakeKeyData(): void
    {
        $data = [
            'bits' => 512,
            'key' => 'x-key',
            'type' => OPENSSL_KEYTYPE_RSA,
            'rsa' => [
                'x' => 'foo',
            ],
        ];
        $key = new Key($data);
        $this->assertSame($data, $key->parsed());
        $this->assertSame(512, $key->numberOfBits());
        $this->assertSame('x-key', $key->publicKeyContents());
        $this->assertTrue($key->type()->isRSA());
        $this->assertTrue($key->isType(OPENSSL_KEYTYPE_RSA));
        $this->assertSame(['x' => 'foo'], $key->typeData());
    }

    public function testUsingEmptyArray(): void
    {
        $key = new Key([]);
        $this->assertSame(0, $key->numberOfBits());
        $this->assertSame('', $key->publicKeyContents());
        $this->assertTrue($key->type()->isRSA());
        $this->assertTrue($key->isType(0));
        $this->assertSame([], $key->typeData());
    }

    public function testUsingInvalidType(): void
    {
        $key = new Key(['type' => -1]);
        $this->expectException(IndexNotFoundException::class);
        $key->type();
    }
}
