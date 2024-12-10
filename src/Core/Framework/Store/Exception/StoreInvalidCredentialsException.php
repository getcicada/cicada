<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Store\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;

#[Package('member')]
class StoreInvalidCredentialsException extends CicadaHttpException
{
    public function __construct()
    {
        parent::__construct('Invalid credentials');
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__STORE_INVALID_CREDENTIALS';
    }
}