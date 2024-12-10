<?php declare(strict_types=1);

namespace Cicada\Core\System\SystemConfig\Facade;

use Doctrine\DBAL\Connection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Script\Execution\Awareness\HookServiceFactory;
use Cicada\Core\Framework\Script\Execution\Awareness\ChannelContextAware;
use Cicada\Core\Framework\Script\Execution\Hook;
use Cicada\Core\Framework\Script\Execution\Script;
use Cicada\Core\System\SystemConfig\SystemConfigService;

/**
 * @internal
 */
#[Package('services-settings')]
class SystemConfigFacadeHookFactory extends HookServiceFactory
{
    /**
     * @internal
     */
    public function __construct(
        private readonly SystemConfigService $systemConfigService,
        private readonly Connection $connection
    ) {
    }

    public function getName(): string
    {
        return 'config';
    }

    public function factory(Hook $hook, Script $script): SystemConfigFacade
    {
        $channelId = null;

        if ($hook instanceof ChannelContextAware) {
            $channelId = $hook->getChannelContext()->getChannelId();
        }

        return new SystemConfigFacade($this->systemConfigService, $this->connection, $script->getScriptAppInformation(), $channelId);
    }
}
