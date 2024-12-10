<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Event;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\User\Recovery\UserRecoveryRequestEvent;

#[Package('services-settings')]
class BusinessEventRegistry
{
    /**
     * @var list<class-string>
     */
    private array $classes = [
        UserRecoveryRequestEvent::class,
    ];

    /**
     * @internal
     */
    public function __construct()
    {

    }

    /**
     * @param list<class-string> $classes
     */
    public function addClasses(array $classes): void
    {
        /** @var list<class-string> */
        $classes = array_unique(array_merge($this->classes, $classes));

        $this->classes = $classes;
    }

    /**
     * @return list<class-string>
     */
    public function getClasses(): array
    {
        return $this->classes;
    }
}
