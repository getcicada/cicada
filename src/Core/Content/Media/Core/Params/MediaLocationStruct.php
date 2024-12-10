<?php declare(strict_types=1);

namespace Cicada\Core\Content\Media\Core\Params;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Struct;

/**
 * Represents a media location
 *
 * Contains all information to generate the path for a media. Typically used in the media path strategy
 * and build over the database or by the request when the media was uploaded or renamed
 *
 * @final
 */
#[Package('frontend')]
class MediaLocationStruct extends Struct
{
    public function __construct(
        public string $id,
        public ?string $extension,
        public ?string $fileName,
        public ?\DateTimeImmutable $uploadedAt
    ) {
    }
}