<?php declare(strict_types=1);

namespace Cicada\Frontend\Framework\Captcha;

use Psr\Container\ContainerInterface;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Routing\KernelListenerPriorities;
use Cicada\Core\PlatformRequest;
use Cicada\Core\System\Channel\ChannelContext;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Cicada\Frontend\Controller\ErrorController;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal
 */
#[Package('frontend')]
readonly class CaptchaRouteListener implements EventSubscriberInterface
{
    /**
     * @internal
     *
     * @param iterable<AbstractCaptcha> $captchas
     */
    public function __construct(
        private iterable $captchas,
        private SystemConfigService $systemConfigService,
        private ContainerInterface $container
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => [
                ['validateCaptcha', KernelListenerPriorities::KERNEL_CONTROLLER_EVENT_SCOPE_VALIDATE],
            ],
        ];
    }

    public function validateCaptcha(ControllerEvent $event): void
    {
        /** @var bool $captchaAnnotation */
        $captchaAnnotation = $event->getRequest()->attributes->get(PlatformRequest::ATTRIBUTE_CAPTCHA, false);

        if ($captchaAnnotation === false) {
            return;
        }

        /** @var ChannelContext|null $context */
        $context = $event->getRequest()->get(PlatformRequest::ATTRIBUTE_CHANNEL_CONTEXT_OBJECT);

        $channelId = $context ? $context->getChannelId() : null;

        $activeCaptchas = (array) ($this->systemConfigService->get('core.basicInformation.activeCaptchasV2', $channelId) ?? []);

        foreach ($this->captchas as $captcha) {
            $captchaConfig = $activeCaptchas[$captcha->getName()] ?? [];
            $request = $event->getRequest();
            if (
                $captcha->supports($request, $captchaConfig) && !$captcha->isValid($request, $captchaConfig)
            ) {
                if ($captcha->shouldBreak()) {
                    throw CaptchaException::invalid($captcha);
                }

                $violations = $captcha->getViolations();

                $event->setController(fn () => $this->container->get(ErrorController::class)->onCaptchaFailure($violations, $request));

                // Return on first invalid captcha
                return;
            }
        }
    }
}
