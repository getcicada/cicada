<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel\Channel;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;
use Cicada\Core\System\Channel\FrontendApiResponse;

#[Package('core')]
class ContextLoadRouteResponse extends FrontendApiResponse
{
    /**
     * @var ChannelContext
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $object;

    public function __construct(ChannelContext $object)
    {
        parent::__construct($object);
    }

    public function getContext(): ChannelContext
    {
        return $this->object;
    }
}
