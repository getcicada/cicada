<?php
declare(strict_types=1);

namespace Cicada\Frontend\Theme\ConfigLoader;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;

#[Package('frontend')]
abstract class AbstractAvailableThemeProvider
{
    abstract public function getDecorated(): AbstractAvailableThemeProvider;

    /**
     * @return array<string, string>
     */
    abstract public function load(Context $context, bool $activeOnly): array;
}