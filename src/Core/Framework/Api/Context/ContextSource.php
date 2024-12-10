<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Api\Context;

use Cicada\Core\Framework\Log\Package;
use Symfony\Component\Serializer\Attribute\DiscriminatorMap;

#[DiscriminatorMap(typeProperty: 'type', mapping: ['system' => SystemSource::class, 'sales-channel' => ChannelApiSource::class, 'admin-api' => AdminApiSource::class, 'shop-api' => ShopApiSource::class, 'admin-sales-channel-api' => AdminChannelApiSource::class])]
#[Package('core')]
interface ContextSource
{
}
