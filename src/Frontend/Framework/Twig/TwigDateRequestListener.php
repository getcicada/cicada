<?php declare(strict_types=1);

namespace Cicada\Frontend\Framework\Twig;

use Composer\EventDispatcher\EventSubscriberInterface;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\PlatformRequest;
use Cicada\Frontend\Framework\Routing\FrontendRouteScope;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Extension\CoreExtension;

/**
 * @internal
 */
#[Package('frontend')]
class TwigDateRequestListener implements EventSubscriberInterface
{
    final public const TIMEZONE_COOKIE = 'timezone';

    /**
     * @internal
     */
    public function __construct(private readonly ContainerInterface $container)
    {
    }

    /**
     * @deprecated tag:v6.7.0 - reason:return-type-change - return type will be array
     *
     * @return array<string, string>
     */
    public static function getSubscribedEvents()
    {
        return [KernelEvents::REQUEST => 'onKernelRequest'];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!\in_array(FrontendRouteScope::ID, $request->attributes->get(PlatformRequest::ATTRIBUTE_ROUTE_SCOPE, []), true)) {
            return;
        }

        $timezone = (string) $request->cookies->get(self::TIMEZONE_COOKIE);

        if ($timezone === 'UTC' || !$timezone || !\in_array($timezone, timezone_identifiers_list(), true)) {
            // Default will be UTC @see https://symfony.com/doc/current/reference/configuration/twig.html#timezone
            return;
        }

        $twig = $this->container->get('twig');

        if (!$twig->hasExtension(CoreExtension::class)) {
            return;
        }

        /** @var CoreExtension $coreExtension */
        $coreExtension = $twig->getExtension(CoreExtension::class);
        $coreExtension->setTimezone($timezone);
    }
}
