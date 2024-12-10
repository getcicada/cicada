<?php declare(strict_types=1);

namespace Cicada\Frontend\Theme\Event;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\CicadaEvent;
use Cicada\Core\Framework\Log\Package;
use Symfony\Contracts\EventDispatcher\Event;

#[Package('frontend')]
class ThemeCompilerEnrichScssVariablesEvent extends Event implements CicadaEvent
{
    /**
     * @param array<string, string|int> $variables
     */
    public function __construct(
        private array $variables,
        private readonly string $channelId,
        private readonly Context $context
    ) {
    }

    public function addVariable(string $name, string $value, bool $sanitize = false): void
    {
        if ($sanitize) {
            $this->variables[$name] = '\'' . addslashes($value) . '\'';
        } else {
            $this->variables[$name] = $value;
        }
    }

    /**
     * @return array<string, string|int>
     */
    public function getVariables(): array
    {
        return $this->variables;
    }

    public function getChannelId(): string
    {
        return $this->channelId;
    }

    public function getContext(): Context
    {
        return $this->context;
    }
}
