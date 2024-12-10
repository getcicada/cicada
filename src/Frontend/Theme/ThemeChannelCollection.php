<?php declare(strict_types=1);

namespace Cicada\Frontend\Theme;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Collection;

/**
 * @extends Collection<ThemeChannel>
 */
#[Package('frontend')]
class ThemeChannelCollection extends Collection
{
    /**
     * @var ThemeChannel[]
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $elements = [];

    protected function getExpectedClass(): string
    {
        return ThemeChannel::class;
    }
}
