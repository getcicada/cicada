<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Field\Flag;

use Cicada\Core\Framework\Log\Package;

/**
 * Only considered in FkField when resolving the write order of DAL commands.
 * You can flag a FkField as NoConstraint when the mysql storage has no real foreign key constraint for this column because you would
 * produce circular references with it.
 *
 * Examples: `product <> cover <> product_media`
 *           `member <> default_billing <> member_address`
 */
#[Package('core')]
class NoConstraint extends Flag
{
    public function parse(): \Generator
    {
        yield 'no_constraint' => true;
    }
}