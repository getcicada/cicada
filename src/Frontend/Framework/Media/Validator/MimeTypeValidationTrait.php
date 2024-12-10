<?php declare(strict_types=1);

namespace Cicada\Frontend\Framework\Media\Validator;

use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[Package('frontend')]
trait MimeTypeValidationTrait
{
    /**
     * @param array<string, string[]> $allowedMimeTypes
     */
    protected function checkMimeType(UploadedFile $file, array $allowedMimeTypes): bool
    {
        foreach ($allowedMimeTypes as $fileEndings => $mime) {
            $fileEndings = explode('|', $fileEndings);

            if (!\in_array(mb_strtolower($file->getExtension()), $fileEndings, true)
                && !\in_array(mb_strtolower($file->getClientOriginalExtension()), $fileEndings, true)
            ) {
                continue;
            }

            if (\is_array($mime) && \in_array($file->getMimeType(), $mime, true)) {
                return true;
            }
        }

        return false;
    }
}