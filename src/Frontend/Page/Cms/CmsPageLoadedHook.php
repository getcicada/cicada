<?php declare(strict_types=1);

namespace Cicada\Frontend\Page\Cms;

use Cicada\Core\Content\Cms\CmsPageEntity;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Script\Execution\Awareness\ChannelContextAwareTrait;
use Cicada\Core\System\Channel\ChannelContext;
use Cicada\Frontend\Page\PageLoadedHook;

/**
 * Triggered when a CmsPage is loaded
 *
 * @hook-use-case data_loading
 *
 * @since 6.4.8.0
 *
 * @final
 */
#[Package('frontend')]
class CmsPageLoadedHook extends PageLoadedHook
{
    use ChannelContextAwareTrait;

    final public const HOOK_NAME = 'cms-page-loaded';

    public function __construct(
        private readonly CmsPageEntity $page,
        ChannelContext $context
    ) {
        parent::__construct($context->getContext());
        $this->channelContext = $context;
    }

    public function getName(): string
    {
        return self::HOOK_NAME;
    }

    public function getPage(): CmsPageEntity
    {
        return $this->page;
    }
}
