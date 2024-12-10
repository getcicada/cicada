<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Plugin;

use Cicada\Core\Framework\Log\Package;

/**
 * @phpstan-type BundleConfig array{
 *         basePath: string,
 *         views: string[],
 *         technicalName: string,
 *         administration?: array{
 *             path: string,
 *             entryFilePath: string|null,
 *             webpack: string|null,
 *         },
 *         frontend: array{
 *            path: string ,
 *            entryFilePath: string|null,
 *            webpack: string|null,
 *            styleFiles: string[],
 *         }
 *     }
 */
#[Package('core')]
interface BundleConfigGeneratorInterface
{
    /**
     * Returns the bundle config for the webpack plugin injector
     *
     * @return array<string, BundleConfig>
     */
    public function getConfig(): array;
}