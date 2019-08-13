<?php

declare(strict_types=1);

namespace PhpCfdi\Credentials\Internal;

/** @internal  */
class Key
{
    use DataArrayTrait;

    /** @var OpenSslKeyTypeEnum|null */
    private $type;

    public function __construct($dataArray)
    {
        $this->dataArray = $dataArray;
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

    public function typeData(): array
    {
        return $this->extractArray($this->type()->value());
    }

    /**
     * @param int $type one of OPENSSL_KEYTYPE_RSA, OPENSSL_KEYTYPE_DSA, OPENSSL_KEYTYPE_DH, OPENSSL_KEYTYPE_EC
     * @return bool
     */
    public function isType(int $type): bool
    {
        return ($this->type()->index() === $type);
    }
}
