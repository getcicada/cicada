<?php declare(strict_types=1);

namespace Cicada\Frontend\Page\Maintenance;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Script\Execution\Awareness\ChannelContextAwareTrait;
use Cicada\Core\System\Channel\ChannelContext;
use Cicada\Frontend\Page\PageLoadedHook;

/**
 * Triggered when the MaintenancePage is loaded
 *
 * @hook-use-case data_loading
 *
 * @since 6.4.8.0
 *
 * @final
 */
#[Package('frontend')]
class MaintenancePageLoadedHook extends PageLoadedHook
{
    use ChannelContextAwareTrait;

    final public const HOOK_NAME = 'maintenance-page-loaded';

    public function __construct(
        private readonly MaintenancePage $page,
        ChannelContext $context
    ) {
        parent::__construct($context->getContext());
        $this->channelContext = $context;
    }

    public function getName(): string
    {
        return self::HOOK_NAME;
    }

    public function getPage(): MaintenancePage
    {
        return $this->page;
    }
}
