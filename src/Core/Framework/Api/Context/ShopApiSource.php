<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Api\Context;

use Cicada\Core\Framework\Log\Package;

#[Package('core')]
class ShopApiSource extends ChannelApiSource
{
    public string $type = 'shop-api';
}