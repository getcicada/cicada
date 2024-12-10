<?php declare(strict_types=1);

namespace Cicada\Frontend\Framework\Media\Validator;

use Cicada\Core\Framework\Log\Package;
use Cicada\Frontend\Framework\Media\FrontendMediaValidatorInterface;
use Cicada\Frontend\Framework\FrontendFrameworkException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[Package('frontend')]
class FrontendMediaDocumentValidator implements FrontendMediaValidatorInterface
{
    use MimeTypeValidationTrait;

    public function getType(): string
    {
        return 'documents';
    }

    public function validate(UploadedFile $file): void
    {
        $valid = $this->checkMimeType($file, [
            'pdf' => ['application/pdf', 'application/x-pdf'],
        ]);

        if (!$valid) {
            throw FrontendFrameworkException::fileTypeNotAllowed((string) $file->getMimeType(), $this->getType());
        }
    }
}
