<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer;

use Cicada\Core\Framework\DataAbstractionLayer\Field\Field;
use Cicada\Core\Framework\Log\Package;

#[Package('core')]
abstract class BulkEntityExtension
{
    /**
     * @return \Generator<string, list<Field>>
     */
    abstract public function collect(): \Generator;
}
