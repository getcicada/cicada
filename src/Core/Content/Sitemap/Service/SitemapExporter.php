<?php declare(strict_types=1);

namespace Cicada\Core\Content\Sitemap\Service;

use League\Flysystem\FilesystemOperator;
use Psr\Cache\CacheItemPoolInterface;
use Cicada\Core\Content\Sitemap\Event\SitemapGeneratedEvent;
use Cicada\Core\Content\Sitemap\Exception\AlreadyLockedException;
use Cicada\Core\Content\Sitemap\Provider\AbstractUrlProvider;
use Cicada\Core\Content\Sitemap\Struct\SitemapGenerationResult;
use Cicada\Core\Content\Sitemap\Struct\Url;
use Cicada\Core\Content\Sitemap\Struct\UrlResult;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\Aggregate\ChannelDomain\ChannelDomainCollection;
use Cicada\Core\System\Channel\ChannelContext;
use Cicada\Core\System\SystemConfig\Exception\InvalidDomainException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Package('services-settings')]
class SitemapExporter implements SitemapExporterInterface
{
    /**
     * @var array<string, SitemapHandleInterface>
     */
    private array $sitemapHandles = [];

    /**
     * @internal
     *
     * @param iterable<AbstractUrlProvider> $urlProvider
     */
    public function __construct(
        private readonly iterable $urlProvider,
        private readonly CacheItemPoolInterface $cache,
        private readonly int $batchSize,
        private readonly FilesystemOperator $filesystem,
        private readonly SitemapHandleFactoryInterface $sitemapHandleFactory,
        private readonly EventDispatcherInterface $dispatcher
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function generate(ChannelContext $context, bool $force = false, ?string $lastProvider = null, ?int $offset = null): SitemapGenerationResult
    {
        $this->lock($context, $force);

        try {
            $this->initSitemapHandles($context);

            foreach ($this->urlProvider as $urlProvider) {
                do {
                    $result = $urlProvider->getUrls($context, $this->batchSize, $offset);

                    $this->processSiteMapHandles($result);

                    $needRun = $result->getNextOffset() !== null;
                    $offset = $result->getNextOffset();
                } while ($needRun);
            }

            $this->finishSitemapHandles();
        } finally {
            $this->unlock($context);
        }

        $this->dispatcher->dispatch(new SitemapGeneratedEvent($context));

        return new SitemapGenerationResult(
            true,
            $lastProvider,
            null,
            $context->getChannel()->getId(),
            $context->getLanguageId()
        );
    }

    private function lock(ChannelContext $channelContext, bool $force): void
    {
        $key = $this->generateCacheKeyForChannel($channelContext);
        $item = $this->cache->getItem($key);
        if ($item->isHit() && !$force) {
            throw new AlreadyLockedException($channelContext);
        }

        $item->set(true);
        $this->cache->save($item);
    }

    private function unlock(ChannelContext $channelContext): void
    {
        $this->cache->deleteItem($this->generateCacheKeyForChannel($channelContext));
    }

    private function generateCacheKeyForChannel(ChannelContext $channelContext): string
    {
        return \sprintf('sitemap-exporter-running-%s-%s', $channelContext->getChannel()->getId(), $channelContext->getLanguageId());
    }

    private function initSitemapHandles(ChannelContext $context): void
    {
        $languageId = $context->getLanguageId();
        $domainsEntity = $context->getChannel()->getDomains();

        $sitemapDomains = [];
        if ($domainsEntity instanceof ChannelDomainCollection) {
            foreach ($domainsEntity as $domain) {
                if ($domain->getLanguageId() === $languageId) {
                    $urlParts = \parse_url($domain->getUrl());

                    if ($urlParts === false) {
                        continue;
                    }

                    $arrayKey = ($urlParts['host'] ?? '') . ($urlParts['path'] ?? '');

                    if (\array_key_exists($arrayKey, $sitemapDomains) && $sitemapDomains[$arrayKey]['scheme'] === 'https') {
                        // NEXT-21735 - does not execute on every test run
                        continue;
                    }

                    $sitemapDomains[$arrayKey] = [
                        'domainId' => $domain->getId(),
                        'url' => $domain->getUrl(),
                        'scheme' => $urlParts['scheme'] ?? '',
                    ];
                }
            }
        }

        $sitemapHandles = [];
        foreach ($sitemapDomains as $sitemapDomain) {
            /** @phpstan-ignore-next-line This ignore should be removed when the deprecated method signature is updated */
            $sitemapHandles[$sitemapDomain['url']] = $this->sitemapHandleFactory->create($this->filesystem, $context, $sitemapDomain['url'], $sitemapDomain['domainId']);
        }

        if (empty($sitemapHandles)) {
            throw new InvalidDomainException('Empty domain');
        }

        $this->sitemapHandles = $sitemapHandles;
    }

    private function processSiteMapHandles(UrlResult $result): void
    {
        /** @var SitemapHandle $sitemapHandle */
        foreach ($this->sitemapHandles as $host => $sitemapHandle) {
            /** @var Url[] $urls */
            $urls = [];

            foreach ($result->getUrls() as $url) {
                $newUrl = clone $url;
                $newUrl->setLoc(rtrim($host, '/') . '/' . ltrim($newUrl->getLoc(), '/'));
                $urls[] = $newUrl;
            }

            $sitemapHandle->write($urls);
        }
    }

    private function finishSitemapHandles(): void
    {
        /** @var SitemapHandle $sitemapHandle */
        foreach ($this->sitemapHandles as $index => $sitemapHandle) {
            if ($index === array_key_first($this->sitemapHandles)) {
                $sitemapHandle->finish();

                continue;
            }

            $sitemapHandle->finish(false);
        }
    }
}
