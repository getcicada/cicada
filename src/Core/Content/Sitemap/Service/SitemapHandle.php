<?php declare(strict_types=1);

namespace Cicada\Core\Content\Sitemap\Service;

use League\Flysystem\FilesystemOperator;
use Cicada\Core\Content\Sitemap\Event\SitemapFilterOpenTagEvent;
use Cicada\Core\Content\Sitemap\SitemapException;
use Cicada\Core\Content\Sitemap\Struct\Url;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[Package('services-settings')]
class SitemapHandle implements SitemapHandleInterface
{
    private const MAX_URLS = 49999;
    private const SITEMAP_NAME_PATTERN = 'sitemap%s-%d.xml.gz';

    /**
     * @var array<string>
     */
    private array $tmpFiles = [];

    /**
     * @var resource
     */
    private $handle;

    private int $index = 1;

    private int $urlCount = 0;

    private ?string $domainName = null;

    private ?string $domainId;

    /**
     * @internal
     */
    public function __construct(
        private readonly FilesystemOperator $filesystem,
        private readonly ChannelContext $context,
        private readonly EventDispatcherInterface $eventDispatcher,
        ?string $domain = null,
        ?string $domainId = null
    ) {
        $this->domainId = $domainId;

        $this->setDomainName($domain);

        $filePath = $this->getTmpFilePath($context);
        $this->openGzip($filePath);
        $this->printHeader();

        $this->tmpFiles[] = $filePath;
    }

    /**
     * @param Url[] $urls
     */
    public function write(array $urls): void
    {
        foreach ($urls as $url) {
            gzwrite($this->handle, (string) $url);
            ++$this->urlCount;

            if ($this->urlCount % self::MAX_URLS === 0) {
                $this->printFooter();
                gzclose($this->handle);
                ++$this->index;
                $path = $this->getTmpFilePath($this->context);
                $this->openGzip($path);
                $this->printHeader();
                $this->tmpFiles[] = $path;
            }
        }
    }

    public function finish(?bool $cleanUp = true): void
    {
        if ($cleanUp) {
            $this->cleanUp();
        }

        if (\is_resource($this->handle)) {
            $this->printFooter();
            gzclose($this->handle);
        }

        foreach ($this->tmpFiles as $i => $tmpFile) {
            $sitemapPath = $this->getFilePath($i + 1, $this->context);
            if ($this->filesystem->fileExists($sitemapPath)) {
                $this->filesystem->delete($sitemapPath);
            }

            $fileContents = file_get_contents($tmpFile);

            if ($fileContents === false) {
                throw SitemapException::fileNotReadable($tmpFile);
            }

            $this->filesystem->write($sitemapPath, $fileContents);

            @unlink($tmpFile);
        }
    }

    private function getFilePath(int $index, ChannelContext $channelContext): string
    {
        return $this->getPath($channelContext) . $this->getFileName($channelContext, $index);
    }

    private function getPath(ChannelContext $channelContext): string
    {
        return 'sitemap/channel-' . $channelContext->getChannel()->getId() . '-' . $channelContext->getLanguageId() . '/';
    }

    private function getTmpFilePath(ChannelContext $channelContext): string
    {
        return rtrim(sys_get_temp_dir(), '/') . '/' . $this->getFileName($channelContext);
    }

    private function getFileName(ChannelContext $channelContext, ?int $index = null): string
    {
        if ($this->domainName === null) {
            return \sprintf($channelContext->getChannel()->getId() . '-' . self::SITEMAP_NAME_PATTERN, null, $index ?? $this->index);
        }

        if ($this->domainId === null) {
            return \sprintf($channelContext->getChannel()->getId() . '-' . self::SITEMAP_NAME_PATTERN, '-' . $this->domainName, $index ?? $this->index);
        }

        return \sprintf($channelContext->getChannel()->getId() . '-' . $this->domainId . '-' . self::SITEMAP_NAME_PATTERN, '-' . $this->domainName, $index ?? $this->index);
    }

    private function printHeader(): void
    {
        /** @var SitemapFilterOpenTagEvent $sitemapOpenTagEvent */
        $sitemapOpenTagEvent = $this->eventDispatcher->dispatch(
            new SitemapFilterOpenTagEvent($this->context)
        );

        gzwrite($this->handle, $sitemapOpenTagEvent->getFullOpenTag());
    }

    private function printFooter(): void
    {
        gzwrite($this->handle, '</urlset>');
    }

    private function cleanUp(): void
    {
        try {
            $files = $this->filesystem->listContents($this->getPath($this->context));
        } catch (\Throwable) {
            // Folder does not exists
            return;
        }

        foreach ($files as $file) {
            $this->filesystem->delete($file->path());
        }
    }

    private function setDomainName(?string $domain = null): void
    {
        if ($domain === null) {
            return;
        }

        $host = parse_url($domain, \PHP_URL_HOST);
        if ($host) {
            $host = str_replace('.', '-', $host);
        }

        $path = parse_url($domain, \PHP_URL_PATH);
        if ($path) {
            $path = str_replace('/', '-', $path);
        }

        $this->domainName = $host . $path;
    }

    private function openGzip(string $filePath): void
    {
        $handle = gzopen($filePath, 'ab');
        if ($handle === false) {
            throw SitemapException::fileNotReadable($filePath);
        }

        $this->handle = $handle;
    }
}