<?php declare(strict_types=1);

namespace Cicada\Frontend\Framework\Twig;

use Cicada\Core\Framework\Log\Package;
use Cicada\Frontend\Framework\Twig\TokenParser\IconTokenParser;
use Twig\Extension\AbstractExtension;

#[Package('frontend')]
class IconExtension extends AbstractExtension
{
    /**
     * @internal
     */
    public function __construct()
    {
    }

    public function getTokenParsers(): array
    {
        return [
            new IconTokenParser(),
        ];
    }
}
