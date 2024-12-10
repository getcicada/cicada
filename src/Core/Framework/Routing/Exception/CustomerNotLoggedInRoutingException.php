<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Routing\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Routing\RoutingException;

#[Package('member')]
class MemberNotLoggedInRoutingException extends RoutingException
{
}
