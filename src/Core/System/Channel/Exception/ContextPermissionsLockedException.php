<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;
use Symfony\Component\HttpFoundation\Response;

#[Package('core')]
class ContextPermissionsLockedException extends CicadaHttpException
{
    public function __construct()
    {
        parent::__construct('Context permission in Channel context already locked.');
    }

    public function getErrorCode(): string
    {
        return 'CHECKOUT__CONTEXT_PERMISSIONS_LOCKED';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}
