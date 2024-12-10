<?php declare(strict_types=1);

namespace Cicada\Frontend\Framework\Cookie;

use Cicada\Core\Framework\Log\Package;

#[Package('frontend')]
interface CookieProviderInterface
{
    /**
     * @return array<string|int, mixed>
     */
    public function getCookieGroups(): array;
}
