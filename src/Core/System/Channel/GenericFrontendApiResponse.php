<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Struct;

/**
 * @internal
 */
#[Package('core')]
class GenericFrontendApiResponse extends FrontendApiResponse
{
    public function __construct(
        int $code,
        Struct $object
    ) {
        $this->setStatusCode($code);

        parent::__construct($object);
    }
}
