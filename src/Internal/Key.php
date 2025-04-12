<?php

declare(strict_types=1);

namespace PhpCfdi\Credentials\Internal;

/** @internal  */
class Key
{
    use DataArrayTrait;

    private ?OpenSslKeyTypeEnum $type = null;

    /** @param array<mixed> $dataArray */
    public function __construct(array $dataArray)
    {
        $this->dataArray = $dataArray;
    }

    /** @return array<mixed> */
    public function parsed(): array
    {
        return $this->dataArray;
    }

    public function publicKeyContents(): string
    {
        return $this->extractString('key');
    }

    public function numberOfBits(): int
    {
        return $this->extractInteger('bits');
    }

    public function type(): OpenSslKeyTypeEnum
    {
        if (null === $this->type) {
            $this->type = new OpenSslKeyTypeEnum($this->extractInteger('type'));
        }
        return $this->type;
    }

    /** @return array<mixed> */
    public function typeData(): array
    {
        return $this->extractArray($this->type()->value());
    }

    /**
     * @param int $type one of OPENSSL_KEYTYPE_RSA, OPENSSL_KEYTYPE_DSA, OPENSSL_KEYTYPE_DH, OPENSSL_KEYTYPE_EC
     */
    public function isType(int $type): bool
    {
        return $this->type()->index() === $type;
    }
}
