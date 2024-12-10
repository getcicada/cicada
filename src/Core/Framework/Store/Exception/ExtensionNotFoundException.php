<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Store\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Store\StoreException;

#[Package('member')]
class ExtensionNotFoundException extends StoreException
{
}
