<?php declare(strict_types=1);

namespace Cicada\Core\Content\Blog\Aggregate\BlogTranslation;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<BlogTranslationEntity>
 */
#[Package('content')]
class BlogTranslationCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'blog_translation_collection';
    }

    protected function getExpectedClass(): string
    {
        return BlogTranslationEntity::class;
    }
}