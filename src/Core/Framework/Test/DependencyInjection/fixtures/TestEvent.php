<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Test\DependencyInjection\fixtures;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\EventData\EventDataCollection;
use Cicada\Core\Framework\Event\FlowEventAware;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @internal
 */
class TestEvent extends Event implements FlowEventAware
{
    final public const EVENT_NAME = 'test.event';

    public function __construct(
        private readonly Context $context,
    ) {
    }

    public function getName(): string
    {
        return self::EVENT_NAME;
    }


    public function getContext(): Context
    {
        return $this->context;
    }

    public static function getAvailableData(): EventDataCollection
    {
        return (new EventDataCollection());
    }
}
