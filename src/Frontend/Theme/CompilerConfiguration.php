<?php declare(strict_types=1);

namespace Cicada\Frontend\Theme;

use Cicada\Core\Framework\Log\Package;

/**
 * @internal - may be changed in the future
 */
#[Package('frontend')]
class CompilerConfiguration extends AbstractCompilerConfiguration
{
    /**
     * @param array<string, mixed> $configuration
     */
    public function __construct(private readonly array $configuration)
    {
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    /**
     * @return mixed
     */
    public function getValue(string $key)
    {
        return $this->configuration[$key] ?? null;
    }
}