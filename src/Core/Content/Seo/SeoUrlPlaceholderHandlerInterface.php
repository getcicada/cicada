<?php declare(strict_types=1);

namespace Cicada\Core\Content\Seo;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;

#[Package('content')]
interface SeoUrlPlaceholderHandlerInterface
{
    /**
     * @param string $name
     * @param array<mixed> $parameters
     */
    public function generate($name, array $parameters = []): string;

    public function replace(string $content, string $host, ChannelContext $context): string;
}
