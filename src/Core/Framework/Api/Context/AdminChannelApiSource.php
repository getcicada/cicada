<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Api\Context;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;

#[Package('core')]
class AdminChannelApiSource extends ChannelApiSource
{
    public string $type = 'admin-sales-channel-api';

    /**
     * @var Context
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $originalContext;

    public function __construct(
        string $channelId,
        Context $originalContext
    ) {
        parent::__construct($channelId);

        $this->originalContext = $originalContext;
    }

    public function getOriginalContext(): Context
    {
        return $this->originalContext;
    }
}
