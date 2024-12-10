<?php declare(strict_types=1);

namespace Cicada\Core\Content\Seo\MainCategory;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<MainCategoryEntity>
 */
#[Package('content')]
class MainCategoryCollection extends EntityCollection
{
    public function filterByChannelId(string $id): MainCategoryCollection
    {
        return $this->filter(static fn (MainCategoryEntity $mainCategory) => $mainCategory->getChannelId() === $id);
    }

    public function getApiAlias(): string
    {
        return 'seo_main_category_collection';
    }

    protected function getExpectedClass(): string
    {
        return MainCategoryEntity::class;
    }
}
