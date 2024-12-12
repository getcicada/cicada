<?php declare(strict_types=1);

namespace Cicada\Core\Content\Breadcrumb\Channel;

use Cicada\Core\Content\Breadcrumb\Struct\BreadcrumbCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\StoreApiResponse;

/**
 * @experimental stableVersion:v6.7.0 feature:BREADCRUMB_STORE_API
 */
#[Package('content')]
class BreadcrumbRouteResponse extends StoreApiResponse
{
    /**
     * @var BreadcrumbCollection
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $object;

    public function __construct(BreadcrumbCollection $breadcrumb)
    {
        parent::__construct($breadcrumb);
    }

    public function getBreadcrumbCollection(): BreadcrumbCollection
    {
        return $this->object;
    }
}
