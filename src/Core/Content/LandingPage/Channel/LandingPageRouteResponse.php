<?php declare(strict_types=1);

namespace Cicada\Core\Content\LandingPage\Channel;

use Cicada\Core\Content\LandingPage\LandingPageEntity;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\StoreApiResponse;

#[Package('frontend')]
class LandingPageRouteResponse extends StoreApiResponse
{
    /**
     * @var LandingPageEntity
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $object;

    public function __construct(LandingPageEntity $landingPage)
    {
        parent::__construct($landingPage);
    }

    public function getLandingPage(): LandingPageEntity
    {
        return $this->object;
    }
}
