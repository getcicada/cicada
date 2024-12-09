<?php declare(strict_types=1);

namespace Cicada\Core\System;

use Cicada\Core\Framework\Bundle;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('core')]
class System extends Bundle
{
    public function getTemplatePriority(): int
    {
        return -1;
    }

}