<?php declare(strict_types=1);

namespace Cicada\Frontend\Page;

use Cicada\Core\Framework\DataAbstractionLayer\Facade\RepositoryFacadeHookFactory;
use Cicada\Core\Framework\DataAbstractionLayer\Facade\ChannelRepositoryFacadeHookFactory;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Routing\Facade\RequestFacadeFactory;
use Cicada\Core\Framework\Script\Execution\Awareness\ChannelContextAware;
use Cicada\Core\Framework\Script\Execution\Hook;
use Cicada\Core\System\SystemConfig\Facade\SystemConfigFacadeHookFactory;

/**
 * @internal only rely on the concrete implementations
 */
#[Package('frontend')]
abstract class PageLoadedHook extends Hook implements ChannelContextAware
{
    /**
     * @return string[]
     */
    public static function getServiceIds(): array
    {
        return [
            RepositoryFacadeHookFactory::class,
            SystemConfigFacadeHookFactory::class,
            ChannelRepositoryFacadeHookFactory::class,
            RequestFacadeFactory::class,
        ];
    }
}
