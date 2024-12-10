<?php declare(strict_types=1);

namespace Cicada\Core\Content\Seo\SeoUrl;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<SeoUrlEntity>
 */
#[Package('content')]
class SeoUrlCollection extends EntityCollection
{
    public function filterByChannelId(string $id): SeoUrlCollection
    {
        return $this->filter(static fn (SeoUrlEntity $seoUrl) => $seoUrl->getChannelId() === $id);
    }

    public function getApiAlias(): string
    {
        return 'seo_url_collection';
    }

    protected function getExpectedClass(): string
    {
        return SeoUrlEntity::class;
    }
}