<?php declare(strict_types=1);

namespace Cicada\Core\Content\Blog\Events;

use Cicada\Core\Framework\Log\Package;

#[Package('content')]
interface BlogChangedEventInterface
{
    /**
     * @return list<string>
     */
    public function getIds(): array;
}
