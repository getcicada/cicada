<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Store\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;
use Symfony\Component\HttpFoundation\Response;

#[Package('member')]
class LicenseNotFoundException extends CicadaHttpException
{
    public function __construct(
        int $licenseId,
        array $parameters = [],
        ?\Throwable $e = null
    ) {
        $parameters['licenseId'] = $licenseId;

        parent::__construct('Could not find license with id {{licenseId}}', $parameters, $e);
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__LICENSE_NOT_FOUND';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }
}
