<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel\Api;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\FrontendApiResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal
 */
#[Package('core')]
class FrontendApiResponseListener implements EventSubscriberInterface
{
    /**
     * @internal
     */
    public function __construct(private readonly StructEncoder $encoder)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['encodeResponse', 10000],
        ];
    }

    public function encodeResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();

        if (!$response instanceof FrontendApiResponse) {
            return;
        }

        $includes = $event->getRequest()->get('includes', []);

        if (!\is_array($includes)) {
            $includes = explode(',', $includes);
        }

        $fields = new ResponseFields($includes);

        $encoded = $this->encoder->encode($response->getObject(), $fields);

        $event->setResponse(new JsonResponse($encoded, $response->getStatusCode(), $response->headers->all()));
    }
}
