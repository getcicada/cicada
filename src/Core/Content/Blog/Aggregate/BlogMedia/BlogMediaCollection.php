<?php declare(strict_types=1);

namespace Cicada\Core\Content\Blog\Aggregate\BlogMedia;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;
/**
 * @extends EntityCollection<BlogMediaEntity>
 */
#[Package('content')]
class BlogMediaCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'blog_media_collection';
    }
    protected function getExpectedClass(): string
    {
        return BlogMediaEntity::class;
    }
}