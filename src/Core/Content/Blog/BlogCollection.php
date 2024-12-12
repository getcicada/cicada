<?php declare(strict_types=1);

namespace Cicada\Core\Content\Blog;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

#[Package('content')]
class BlogCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return BlogEntity::class;
    }
}