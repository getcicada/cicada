<?php declare(strict_types=1);

namespace Cicada\Frontend\Framework\Routing;

use Cicada\Core\Framework\Event\BeforeSendResponseEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\ChannelRequest;

/**
 * @internal
 */
#[Package('core')]
class CanonicalLinkListener
{
    public function __invoke(BeforeSendResponseEvent $event): void
    {
        if (!$event->getResponse()->isSuccessful()) {
            return;
        }

        if ($canonical = $event->getRequest()->attributes->get(ChannelRequest::ATTRIBUTE_CANONICAL_LINK)) {
            \assert(\is_string($canonical));
            $canonical = \sprintf('<%s>; rel="canonical"', $canonical);
            $event->getResponse()->headers->set('Link', $canonical);
        }
    }
}
