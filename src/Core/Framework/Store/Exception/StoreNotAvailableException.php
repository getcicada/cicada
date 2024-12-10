<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Store\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;

#[Package('member')]
class StoreNotAvailableException extends CicadaHttpException
{
    public function __construct()
    {
        parent::__construct('Store is not available');
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__STORE_NOT_AVAILABLE';
    }
}