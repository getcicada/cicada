<?php
declare(strict_types=1);

namespace Cicada\Frontend\Framework\Twig;

use Cicada\Core\Framework\Adapter\Twig\TemplateFinder;
use Cicada\Core\Framework\Log\Package;
use Cicada\Frontend\Framework\Twig\TokenParser\ThumbnailTokenParser;
use Twig\Extension\AbstractExtension;

#[Package('frontend')]
class ThumbnailExtension extends AbstractExtension
{
    /**
     * @internal
     */
    public function __construct(private readonly TemplateFinder $finder)
    {
    }

    public function getTokenParsers(): array
    {
        return [
            new ThumbnailTokenParser(),
        ];
    }

    public function getFinder(): TemplateFinder
    {
        return $this->finder;
    }
}
