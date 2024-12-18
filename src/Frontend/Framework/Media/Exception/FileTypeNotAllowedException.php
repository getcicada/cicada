<?php declare(strict_types=1);

namespace Cicada\Frontend\Framework\Media\Exception;

use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;

/**
 * @deprecated tag:v6.7.0 - Will be removed, use FrontendFrameworkException::fileTypeNotAllowed instead
 */
#[Package('frontend')]
class FileTypeNotAllowedException extends CicadaHttpException
{
    public function __construct(
        string $mimeType,
        string $uploadType
    ) {
        parent::__construct(
            'Type "{{ mimeType }}" of provided file is not allowed for {{ uploadType }}',
            ['mimeType' => $mimeType, 'uploadType' => $uploadType]
        );
    }

    public function getErrorCode(): string
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', Feature::deprecatedClassMessage(self::class, 'v6.7.0.0', 'FrontendFrameworkException'));

        return 'STOREFRONT__MEDIA_ILLEGAL_FILE_TYPE';
    }
}
