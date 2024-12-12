<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Write\Validation;

use Cicada\Core\Framework\Log\Package;

#[Package('core')]
class RestrictDeleteViolation
{
    /**
     * @param mixed[][] $restrictions
     */
    public function __construct(
        private readonly array $restrictions
    ) {
    }

    public function getRestrictions(): array
    {
        return $this->restrictions;
    }
}
