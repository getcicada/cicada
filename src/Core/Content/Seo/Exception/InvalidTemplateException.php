<?php declare(strict_types=1);

namespace Cicada\Core\Content\Seo\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;
use Symfony\Component\HttpFoundation\Response;

#[Package('content')]
class InvalidTemplateException extends CicadaHttpException
{
    final public const ERROR_CODE = 'FRAMEWORK__INVALID_SEO_TEMPLATE';

    public function __construct(string $message)
    {
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function getErrorCode(): string
    {
        return self::ERROR_CODE;
    }
}
