<?php

declare(strict_types=1);

namespace Cicada\Core\Content\Media\Thumbnail;

use Cicada\Core\Content\Media\Aggregate\MediaThumbnailSize\MediaThumbnailSizeEntity;
use Cicada\Core\Framework\Log\Package;

/**
 * @phpstan-import-type ImageSize from ThumbnailService
 *
 * @final
 */
#[Package('frontend')]
class ThumbnailSizeCalculator
{
    /**
     * @param ImageSize $imageSize
     *
     * @return ImageSize
     */
    public function calculate(
        array $imageSize,
        MediaThumbnailSizeEntity $preferredThumbnailSize
    ): array {
        if ($imageSize['width'] >= $imageSize['height']) {
            $factor = $preferredThumbnailSize->getWidth() / $imageSize['width'];
            if ($preferredThumbnailSize->getHeight() < $imageSize['height'] * $factor) {
                $factor = $preferredThumbnailSize->getHeight() / $imageSize['height'];
            }
        } else {
            $factor = $preferredThumbnailSize->getHeight() / $imageSize['height'];
            if ($preferredThumbnailSize->getWidth() < $imageSize['width'] * $factor) {
                $factor = $preferredThumbnailSize->getWidth() / $imageSize['width'];
            }
        }

        $calculatedWidth = (int) round($imageSize['width'] * $factor);
        \assert($calculatedWidth > 0);
        $calculatedHeight = (int) round($imageSize['height'] * $factor);
        \assert($calculatedHeight > 0);

        return $this->determineValidSize($imageSize, $calculatedWidth, $calculatedHeight);
    }

    /**
     * @param ImageSize $imageSize
     * @param int<1, max> $thumbnailWith
     * @param int<1, max> $thumbnailHeight
     *
     * @return ImageSize
     */
    public function determineValidSize(
        array $imageSize,
        int $thumbnailWith,
        int $thumbnailHeight
    ): array {
        $useOriginalSizeInThumbnails = $imageSize['width'] < $thumbnailWith || $imageSize['height'] < $thumbnailHeight;

        return $useOriginalSizeInThumbnails ? [
            'width' => $imageSize['width'],
            'height' => $imageSize['height'],
        ] : [
            'width' => $thumbnailWith,
            'height' => $thumbnailHeight,
        ];
    }
}