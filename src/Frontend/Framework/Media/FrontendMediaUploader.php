<?php declare(strict_types=1);

namespace Cicada\Frontend\Framework\Media;

use Cicada\Core\Content\Media\File\FileSaver;
use Cicada\Core\Content\Media\File\MediaFile;
use Cicada\Core\Content\Media\MediaException;
use Cicada\Core\Content\Media\MediaService;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Frontend\Framework\FrontendFrameworkException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[Package('frontend')]
class FrontendMediaUploader
{
    /**
     * @internal
     */
    public function __construct(
        private readonly MediaService $mediaService,
        private readonly FileSaver $fileSaver,
        private readonly FrontendMediaValidatorRegistry $validator
    ) {
    }

    /**
     * @throws FrontendFrameworkException
     * @throws MediaException
     */
    public function upload(UploadedFile $file, string $folder, string $type, Context $context, bool $isPrivate = false): string
    {
        $this->checkValidFile($file);

        $this->validator->validate($file, $type);

        $mediaFile = new MediaFile(
            $file->getPathname(),
            $file->getMimeType() ?? '',
            $file->getClientOriginalExtension(),
            $file->getSize() ?: 0
        );

        $mediaId = $this->mediaService->createMediaInFolder($folder, $context, $isPrivate);

        $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($mediaFile, $mediaId): void {
            $this->fileSaver->persistFileToMedia(
                $mediaFile,
                pathinfo(Uuid::randomHex(), \PATHINFO_FILENAME),
                $mediaId,
                $context
            );
        });

        return $mediaId;
    }

    private function checkValidFile(UploadedFile $file): void
    {
        if (!$file->isValid()) {
            throw MediaException::invalidFile($file->getErrorMessage());
        }

        if (preg_match('/.+\.ph(p([3457s]|-s)?|t|tml)/', $file->getFilename())) {
            throw MediaException::illegalFileName($file->getFilename(), 'contains PHP related file extension');
        }
    }
}
