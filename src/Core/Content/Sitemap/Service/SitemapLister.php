<?php declare(strict_types=1);

namespace Cicada\Core\Content\Sitemap\Service;

use League\Flysystem\FilesystemOperator;
use Cicada\Core\Content\Sitemap\Struct\Sitemap;
use Cicada\Core\System\Channel\Aggregate\ChannelDomain\ChannelDomainCollection;
use Cicada\Core\System\Channel\ChannelContext;
use Symfony\Component\Asset\Package;

#[\Cicada\Core\Framework\Log\Package('services-settings')]
class SitemapLister implements SitemapListerInterface
{
    /**
     * @internal
     */
    public function __construct(
        private readonly FilesystemOperator $filesystem,
        private readonly Package $package
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getSitemaps(ChannelContext $channelContext): array
    {
        $files = $this->filesystem->listContents('sitemap/channel-' . $channelContext->getChannel()->getId() . '-' . $channelContext->getLanguageId());

        $sitemaps = [];

        /** @var ChannelDomainCollection $domains */
        $domains = $channelContext->getChannel()->getDomains();

        foreach ($files as $file) {
            if ($file->isDir()) {
                continue;
            }

            $filename = basename($file->path());

            $exploded = explode('-', $filename);

            if (isset($exploded[1]) && $domains->has($exploded[1])) {
                $domain = $domains->get($exploded[1]);

                $sitemaps[] = new Sitemap($domain->getUrl() . '/' . $file->path(), 0, new \DateTime('@' . ($file->lastModified() ?? time())));

                continue;
            }

            $sitemaps[] = new Sitemap($this->package->getUrl($file->path()), 0, new \DateTime('@' . ($file->lastModified() ?? time())));
        }

        return $sitemaps;
    }
}