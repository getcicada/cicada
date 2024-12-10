<?php declare(strict_types=1);

namespace Cicada\Frontend\Theme;

use Cicada\Core\Framework\Log\Package;

/**
 * @internal - may be changed in the future
 */
#[Package('frontend')]
abstract class AbstractScssCompiler
{
    abstract public function compileString(
        AbstractCompilerConfiguration $config,
        string $scss,
        ?string $path = null
    ): string;
}
