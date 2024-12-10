<?php declare(strict_types=1);

namespace Cicada\Core\Content\Media\Aggregate\MediaFolder;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<MediaFolderEntity>
 */
#[Package('frontend')]
class MediaFolderCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'media_folder_collection';
    }

    protected function getExpectedClass(): string
    {
        return MediaFolderEntity::class;
    }
}