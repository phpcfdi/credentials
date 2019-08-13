<?php

declare(strict_types=1);

namespace PhpCfdi\Credentials\Tests\Unit\Internal;

use PhpCfdi\Credentials\Internal\LocalFileOpenTrait;

class LocalFileOpenTraitSpecimen
{
    use LocalFileOpenTrait {
        localFileOpen as public;
    }
}
