<?php declare(strict_types=1);

namespace Cicada\Frontend\Framework\Media;

use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[Package('frontend')]
interface FrontendMediaValidatorInterface
{
    /**
     * Returns the supported file type
     */
    public function getType(): string;

    /**
     * Validates the provided file
     */
    public function validate(UploadedFile $file): void;
}
