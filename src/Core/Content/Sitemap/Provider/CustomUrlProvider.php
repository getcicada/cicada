<?php declare(strict_types=1);

namespace Cicada\Core\Content\Sitemap\Provider;

use Cicada\Core\Content\Sitemap\Service\ConfigHandler;
use Cicada\Core\Content\Sitemap\Struct\Url;
use Cicada\Core\Content\Sitemap\Struct\UrlResult;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\System\Channel\ChannelContext;

#[Package('services-settings')]
class CustomUrlProvider extends AbstractUrlProvider
{
    /**
     * @internal
     */
    public function __construct(private readonly ConfigHandler $configHandler)
    {
    }

    public function getDecorated(): AbstractUrlProvider
    {
        throw new DecorationPatternException(self::class);
    }

    public function getName(): string
    {
        return 'custom';
    }

    /**
     * {@inheritdoc}
     */
    public function getUrls(ChannelContext $context, int $limit, ?int $offset = null): UrlResult
    {
        $sitemapCustomUrls = $this->configHandler->get(ConfigHandler::CUSTOM_URLS_KEY);

        $urls = [];
        $url = new Url();
        foreach ($sitemapCustomUrls as $sitemapCustomUrl) {
            if (!$this->isAvailableForChannel($sitemapCustomUrl, $context->getChannel()->getId())) {
                continue;
            }

            $newUrl = clone $url;
            $newUrl->setLoc($sitemapCustomUrl['url']);
            $newUrl->setLastmod($sitemapCustomUrl['lastMod']);
            $newUrl->setChangefreq($sitemapCustomUrl['changeFreq']);
            $newUrl->setResource('custom');
            $newUrl->setIdentifier('');

            $urls[] = $newUrl;
        }

        return new UrlResult($urls, null);
    }

    private function isAvailableForChannel(array $url, ?string $channelId): bool
    {
        return \in_array($url['channelId'], [$channelId, null], true);
    }
}
