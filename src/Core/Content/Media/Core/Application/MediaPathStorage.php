<?php declare(strict_types=1);

namespace Cicada\Core\Content\Media\Core\Application;

use Cicada\Core\Framework\Log\Package;

/**
 * @internal Just for abstraction between domain and infrastructure. No public API!
 */
#[Package('frontend')]
interface MediaPathStorage
{
    /**
     * @param array<string, string> $paths
     */
    public function media(array $paths): void;

    /**
     * @param array<string, string> $paths
     */
    public function thumbnails(array $paths): void;
}
