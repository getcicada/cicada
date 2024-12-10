<?php declare(strict_types=1);

namespace Cicada\Frontend\Page;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Profiling\Profiler;
use Cicada\Core\ChannelRequest;
use Cicada\Core\System\Channel\ChannelContext;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

#[Package('frontend')]
class GenericPageLoader implements GenericPageLoaderInterface
{
    /**
     * @internal
     */
    public function __construct(
        private readonly SystemConfigService $systemConfigService,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function load(Request $request, ChannelContext $context): Page
    {
        return Profiler::trace('generic-page-loader', function () use ($request, $context) {
            $page = new Page();

            $page->setMetaInformation((new MetaInformation())->assign([
                'revisit' => '15 days',
                'robots' => 'index,follow',
                'xmlLang' => $request->attributes->get(ChannelRequest::ATTRIBUTE_DOMAIN_LOCALE) ?? '',
                'metaTitle' => $this->systemConfigService->getString('core.basicInformation.shopName', $context->getChannel()->getId()),
            ]));

            if ($request->isXmlHttpRequest() || $request->attributes->get('_esi', false)) {
                $this->eventDispatcher->dispatch(
                    new GenericPageLoadedEvent($page, $context, $request)
                );

                return $page;
            }

            $this->eventDispatcher->dispatch(
                new GenericPageLoadedEvent($page, $context, $request)
            );

            return $page;
        });
    }
}
