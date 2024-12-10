<?php declare(strict_types=1);

namespace Cicada\Frontend\Controller;

use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\Aggregate\ChannelAnalytics\ChannelAnalyticsCollection;
use Cicada\Core\System\Channel\ChannelContext;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Cicada\Frontend\Framework\Captcha\GoogleReCaptchaV2;
use Cicada\Frontend\Framework\Captcha\GoogleReCaptchaV3;
use Cicada\Frontend\Framework\Cookie\CookieProviderInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Returns the cookie-configuration.html.twig template including all cookies returned by the "getCookieGroup"-method
 *
 * Cookies are returned within groups, groups require the "group" attribute
 * A group is structured as described above the "getCookieGroup"-method
 *
 * @internal
 * Do not use direct or indirect repository calls in a controller. Always use a store-api route to get or put data
 */
#[Route(defaults: ['_routeScope' => ['frontend']])]
#[Package('frontend')]
class CookieController extends FrontendController
{
    /**
     * @internal
     *
     * @param EntityRepository<ChannelAnalyticsCollection> $channelAnalyticsRepository
     */
    public function __construct(
        private readonly CookieProviderInterface $cookieProvider,
        private readonly SystemConfigService $systemConfigService,
        private readonly EntityRepository $channelAnalyticsRepository
    ) {
    }

    #[Route(path: '/cookie/offcanvas', name: 'frontend.cookie.offcanvas', options: ['seo' => false], defaults: ['XmlHttpRequest' => true], methods: ['GET'])]
    public function offcanvas(ChannelContext $context): Response
    {
        $response = $this->renderFrontend('@Frontend/frontend/layout/cookie/cookie-configuration.html.twig', [
            'cookieGroups' => $this->getCookieGroups($context),
        ]);
        $response->headers->set('x-robots-tag', 'noindex,follow');

        return $response;
    }

    #[Route(path: '/cookie/permission', name: 'frontend.cookie.permission', options: ['seo' => false], defaults: ['XmlHttpRequest' => true], methods: ['GET'])]
    public function permission(ChannelContext $context): Response
    {
        $response = $this->renderFrontend('@Frontend/frontend/layout/cookie/cookie-permission.html.twig', [
            'cookieGroups' => $this->getCookieGroups($context),
        ]);
        $response->headers->set('x-robots-tag', 'noindex,follow');

        return $response;
    }

    /**
     * @return array<mixed>
     */
    private function getCookieGroups(ChannelContext $context): array
    {
        $cookieGroups = $this->cookieProvider->getCookieGroups();
        $cookieGroups = $this->filterGoogleAnalyticsCookie($context, $cookieGroups);
        $cookieGroups = $this->filterWishlistCookie($context->getChannelId(), $cookieGroups);
        $cookieGroups = $this->filterGoogleReCaptchaCookie($context->getChannelId(), $cookieGroups);

        return $cookieGroups;
    }

    /**
     * @param array<string|int, mixed> $cookieGroups
     *
     * @return array<string|int, mixed>
     */
    private function filterGoogleAnalyticsCookie(ChannelContext $context, array $cookieGroups): array
    {
        $channel = $context->getChannel();

        if ($channel->getAnalytics() === null && $channel->getAnalyticsId() !== null) {
            $criteria = new Criteria([$channel->getAnalyticsId()]);
            $criteria->setTitle('cookie-controller::load-analytics');

            $channel->setAnalytics(
                $this->channelAnalyticsRepository->search($criteria, $context->getContext())->getEntities()->first()
            );
        }

        if ($channel->getAnalytics()?->isActive() === true) {
            return $cookieGroups;
        }

        $filteredGroups = [];
        foreach ($cookieGroups as $cookieGroup) {
            if ($cookieGroup['snippet_name'] === 'cookie.groupStatistical') {
                $cookieGroup = $this->filterCookieGroup('cookie.groupStatisticalGoogleAnalytics', $cookieGroup);
                if ($cookieGroup !== null) {
                    $filteredGroups[] = $cookieGroup;
                }

                continue;
            } elseif ($cookieGroup['snippet_name'] === 'cookie.groupMarketing') {
                $cookieGroup = $this->filterCookieGroup('cookie.groupMarketingAdConsent', $cookieGroup);
                if ($cookieGroup !== null) {
                    $filteredGroups[] = $cookieGroup;
                }

                continue;
            }

            $filteredGroups[] = $cookieGroup;
        }

        return $filteredGroups;
    }

    /**
     * @param array<string|int, mixed> $cookieGroups
     *
     * @return array<string|int, mixed>
     */
    private function filterWishlistCookie(string $channelId, array $cookieGroups): array
    {
        if ($this->systemConfigService->getBool('core.cart.wishlistEnabled', $channelId)) {
            return $cookieGroups;
        }

        $filteredGroups = [];
        foreach ($cookieGroups as $cookieGroup) {
            if ($cookieGroup['snippet_name'] === 'cookie.groupComfortFeatures') {
                $cookieGroup = $this->filterCookieGroup('cookie.groupComfortFeaturesWishlist', $cookieGroup);
                if ($cookieGroup !== null) {
                    $filteredGroups[] = $cookieGroup;
                }

                continue;
            }

            $filteredGroups[] = $cookieGroup;
        }

        return $filteredGroups;
    }

    /**
     * @param array<string|int, mixed> $cookieGroups
     *
     * @return array<string|int, mixed>
     */
    private function filterGoogleReCaptchaCookie(string $channelId, array $cookieGroups): array
    {
        $googleRecaptchaActive = $this->systemConfigService->getBool(
            'core.basicInformation.activeCaptchasV2.' . GoogleReCaptchaV2::CAPTCHA_NAME . '.isActive',
            $channelId
        ) || $this->systemConfigService->getBool(
            'core.basicInformation.activeCaptchasV2.' . GoogleReCaptchaV3::CAPTCHA_NAME . '.isActive',
            $channelId
        );

        if ($googleRecaptchaActive) {
            return $cookieGroups;
        }

        $filteredGroups = [];
        foreach ($cookieGroups as $cookieGroup) {
            if ($cookieGroup['snippet_name'] === 'cookie.groupRequired') {
                $cookieGroup = $this->filterCookieGroup('cookie.groupRequiredCaptcha', $cookieGroup);
                if ($cookieGroup !== null) {
                    $filteredGroups[] = $cookieGroup;
                }

                continue;
            }

            $filteredGroups[] = $cookieGroup;
        }

        return $filteredGroups;
    }

    /**
     * @param array<mixed> $cookieGroup
     *
     * @return ?array<mixed>
     */
    private function filterCookieGroup(string $cookieSnippetName, array $cookieGroup): ?array
    {
        $cookieGroup['entries'] = array_filter($cookieGroup['entries'], fn ($item) => $item['snippet_name'] !== $cookieSnippetName);
        if (\count($cookieGroup['entries']) === 0) {
            return null;
        }

        return $cookieGroup;
    }
}
