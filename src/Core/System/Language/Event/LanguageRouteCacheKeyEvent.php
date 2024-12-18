<?php declare(strict_types=1);

namespace Cicada\Core\System\Language\Event;

use Cicada\Core\Framework\Adapter\Cache\FrontendApiRouteCacheKeyEvent;
use Cicada\Core\Framework\Log\Package;

#[Package('frontend')]
class LanguageRouteCacheKeyEvent extends FrontendApiRouteCacheKeyEvent
{
}
