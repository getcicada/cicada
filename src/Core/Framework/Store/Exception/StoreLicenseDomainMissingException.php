<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Store\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;

#[Package('member')]
class StoreLicenseDomainMissingException extends CicadaHttpException
{
    public function __construct()
    {
        parent::__construct('Store license domain is missing');
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__STORE_LICENSE_DOMAIN_IS_MISSING';
    }
}