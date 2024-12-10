<?php declare(strict_types=1);

namespace Cicada\Core\Content\Media\Event;

use Cicada\Core\Framework\Log\Package;
use Symfony\Contracts\EventDispatcher\Event;

#[Package('frontend')]
class UnusedMediaSearchEvent extends Event
{
    /**
     * @param array<string> $ids
     */
    public function __construct(private array $ids)
    {
    }

    /**
     * Specify that some IDs should NOT be deleted, they are in fact used.
     *
     * @param array<string> $ids
     */
    public function markAsUsed(array $ids): void
    {
        $this->ids = array_values(array_diff($this->ids, $ids));
    }

    /**
     * @return array<string> $ids
     */
    public function getUnusedIds(): array
    {
        return $this->ids;
    }
}