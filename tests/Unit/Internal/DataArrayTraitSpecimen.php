<?php

declare(strict_types=1);

namespace PhpCfdi\Credentials\Tests\Unit\Internal;

use PhpCfdi\Credentials\Internal\DataArrayTrait;

class DataArrayTraitSpecimen
{
    use DataArrayTrait {
        DataArrayTrait::extractString as public;
        DataArrayTrait::extractInteger as public;
        DataArrayTrait::extractArray as public;
        DataArrayTrait::extractDateTime as public;
    }

    /** @param array<mixed> $dataArray */
    public function __construct(array $dataArray)
    {
        $this->dataArray = $dataArray;
    }
}
