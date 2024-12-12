<?php declare(strict_types=1);

namespace Cicada\Core\Content\Blog\Aggregate\BlogVisibility;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<BlogVisibilityEntity>
 */
#[Package('content')]
class BlogVisibilityCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'blog_visibility_collection';
    }

    protected function getExpectedClass(): string
    {
        return BlogVisibilityEntity::class;
    }
}