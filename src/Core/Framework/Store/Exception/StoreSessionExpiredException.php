<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Store\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;
use Symfony\Component\HttpFoundation\Response;

#[Package('member')]
class StoreSessionExpiredException extends CicadaHttpException
{
    public function __construct()
    {
        parent::__construct('Store session has expired');
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__STORE_SESSION_EXPIRED';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_FORBIDDEN;
    }
}
