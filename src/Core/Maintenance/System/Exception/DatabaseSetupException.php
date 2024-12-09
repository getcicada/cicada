<?php declare(strict_types=1);

namespace Cicada\Core\Maintenance\System\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Maintenance\MaintenanceException;

/**
 * @internal
 */
#[Package('core')]
class DatabaseSetupException extends MaintenanceException
{
}
