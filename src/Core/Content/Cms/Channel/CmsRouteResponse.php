<?php declare(strict_types=1);

namespace Cicada\Core\Content\Cms\Channel;

use Cicada\Core\Content\Cms\CmsPageEntity;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\StoreApiResponse;

#[Package('frontend')]
class CmsRouteResponse extends StoreApiResponse
{
    /**
     * @var CmsPageEntity
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $object;

    public function __construct(CmsPageEntity $object)
    {
        parent::__construct($object);
    }

    public function getCmsPage(): CmsPageEntity
    {
        return $this->object;
    }
}