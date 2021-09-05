<?php

declare(strict_types=1);

namespace PhpCfdi\Credentials\Internal;

use Eclipxe\Enum\Enum;

/**
 * Do not instantiate this class outside this project, is for internal usage
 *
 * @method static self rsa()
 * @method static self dsa()
 * @method static self dh()
 * @method static self ec()
 * @method bool isRSA()
 * @method bool isDSA()
 * @method bool isDH()
 * @method bool isEC()
 *
 * @internal
 */
final class OpenSslKeyTypeEnum extends Enum
{
    /**
     * Override indices to use OPENSSL constants as indices
     *
     * @return array<string, int>
     * @noinspection PhpMissingParentCallCommonInspection
     */
    protected static function overrideIndices(): array
    {
        return [
            'rsa' => OPENSSL_KEYTYPE_RSA,
            'dsa' => OPENSSL_KEYTYPE_DSA,
            'dh' => OPENSSL_KEYTYPE_DH,
            'ec' => OPENSSL_KEYTYPE_EC,
        ];
    }
}
