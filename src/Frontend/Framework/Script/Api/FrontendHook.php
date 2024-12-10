<?php declare(strict_types=1);

namespace Cicada\Frontend\Framework\Script\Api;

use Cicada\Core\Framework\DataAbstractionLayer\Facade\RepositoryFacadeHookFactory;
use Cicada\Core\Framework\DataAbstractionLayer\Facade\RepositoryWriterFacadeHookFactory;
use Cicada\Core\Framework\DataAbstractionLayer\Facade\ChannelRepositoryFacadeHookFactory;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Routing\Facade\RequestFacadeFactory;
use Cicada\Core\Framework\Script\Api\ScriptResponseFactoryFacadeHookFactory;
use Cicada\Core\Framework\Script\Execution\Awareness\ChannelContextAware;
use Cicada\Core\Framework\Script\Execution\Awareness\ScriptResponseAwareTrait;
use Cicada\Core\Framework\Script\Execution\Awareness\StoppableHook;
use Cicada\Core\Framework\Script\Execution\Awareness\StoppableHookTrait;
use Cicada\Core\Framework\Script\Execution\Hook;
use Cicada\Core\System\Channel\ChannelContext;
use Cicada\Core\System\SystemConfig\Facade\SystemConfigFacadeHookFactory;
use Cicada\Frontend\Page\Page;

/**
 * Triggered when the frontend endpoint /frontend/script/{hook} is called
 *
 * @hook-use-case custom_endpoint
 *
 * @since 6.4.9.0
 *
 * @final
 */
#[Package('core')]
class FrontendHook extends Hook implements ChannelContextAware, StoppableHook
{
    use ScriptResponseAwareTrait;
    use StoppableHookTrait;

    final public const HOOK_NAME = 'frontend-{hook}';

    /**
     * @param array<string, mixed> $request
     * @param array<string, mixed> $query
     */
    public function __construct(
        private readonly string $script,
        private readonly array $request,
        private readonly array $query,
        private readonly Page $page,
        private readonly ChannelContext $channelContext
    ) {
        parent::__construct($channelContext->getContext());
    }

    /**
     * @return array<string, mixed>
     */
    public function getRequest(): array
    {
        return $this->request;
    }

    /**
     * @return array<string, mixed>
     */
    public function getQuery(): array
    {
        return $this->query;
    }

    public function getChannelContext(): ChannelContext
    {
        return $this->channelContext;
    }

    public function getName(): string
    {
        return \str_replace(
            ['{hook}'],
            [$this->script],
            self::HOOK_NAME
        );
    }

    public static function getServiceIds(): array
    {
        return [
            RepositoryFacadeHookFactory::class,
            SystemConfigFacadeHookFactory::class,
            ChannelRepositoryFacadeHookFactory::class,
            RepositoryWriterFacadeHookFactory::class,
            ScriptResponseFactoryFacadeHookFactory::class,
            RequestFacadeFactory::class,
        ];
    }

    public function getPage(): Page
    {
        return $this->page;
    }
}
