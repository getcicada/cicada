<?php declare(strict_types=1);

namespace Cicada\Frontend\Theme;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;

#[Package('frontend')]
abstract class AbstractResolvedConfigLoader
{
    abstract public function getDecorated(): AbstractResolvedConfigLoader;

    abstract public function load(string $themeId, ChannelContext $context): array;
}
