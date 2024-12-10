<?php declare(strict_types=1);

namespace Cicada\Frontend\Framework\Twig\Extension;

use Cicada\Core\Content\Media\MediaEntity;
use Cicada\Core\Framework\Log\Package;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

#[Package('frontend')]
class UrlEncodingTwigFilter extends AbstractExtension
{
    /**
     * @return list<TwigFilter>
     */
    public function getFilters()
    {
        return [
            new TwigFilter('sw_encode_url', $this->encodeUrl(...)),
            new TwigFilter('sw_encode_media_url', $this->encodeMediaUrl(...)),
        ];
    }

    public function encodeUrl(?string $mediaUrl): ?string
    {
        if ($mediaUrl === null) {
            return null;
        }

        $urlInfo = parse_url($mediaUrl);
        if (!\is_array($urlInfo)) {
            return null;
        }

        $segments = explode('/', $urlInfo['path'] ?? '');

        foreach ($segments as $index => $segment) {
            $segments[$index] = rawurlencode($segment);
        }

        $path = implode('/', $segments);
        if (isset($urlInfo['query'])) {
            $path .= "?{$urlInfo['query']}";
        }

        $encodedPath = '';

        if (isset($urlInfo['scheme'])) {
            $encodedPath = "{$urlInfo['scheme']}://";
        }

        if (isset($urlInfo['host'])) {
            $encodedPath .= "{$urlInfo['host']}";
        }

        if (isset($urlInfo['port'])) {
            $encodedPath .= ":{$urlInfo['port']}";
        }

        return $encodedPath . $path;
    }

    public function encodeMediaUrl(?MediaEntity $media): ?string
    {
        if ($media === null || !$media->hasFile()) {
            return null;
        }

        return $this->encodeUrl($media->getUrl());
    }
}