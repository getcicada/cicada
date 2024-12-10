<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel\Channel;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\System\Channel\ContextTokenResponse;
use Cicada\Core\System\Channel\ChannelContext;

/**
 * This route allows changing configurations inside the context.
 * Following parameters are allowed to change: "currencyId", "languageId", "billingAddressId", "shippingAddressId",
 * "paymentMethodId", "shippingMethodId", "countryId" and "countryStateId"
 */
#[Package('core')]
abstract class AbstractContextSwitchRoute
{
    abstract public function getDecorated(): AbstractContextSwitchRoute;

    abstract public function switchContext(RequestDataBag $data, ChannelContext $context): ContextTokenResponse;
}
