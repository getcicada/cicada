<?php declare(strict_types=1);

namespace Cicada\Core\Content\Sitemap\Channel;

use Cicada\Core\Content\Sitemap\Struct\SitemapCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\StoreApiResponse;

#[Package('services-settings')]
class SitemapRouteResponse extends StoreApiResponse
{
    /**
     * @var SitemapCollection
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $object;

    public function __construct(SitemapCollection $object)
    {
        parent::__construct($object);
    }

    public function getSitemaps(): SitemapCollection
    {
        return $this->object;
    }
}
