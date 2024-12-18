<?php declare(strict_types=1);

namespace Cicada\Core\Content\Media;

use Cicada\Core\Checkout\Document\Aggregate\DocumentBaseConfig\DocumentBaseConfigCollection;
use Cicada\Core\Checkout\Document\DocumentCollection;
use Cicada\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Cicada\Core\Checkout\Order\Aggregate\OrderLineItemDownload\OrderLineItemDownloadCollection;
use Cicada\Core\Checkout\Payment\PaymentMethodCollection;
use Cicada\Core\Checkout\Shipping\ShippingMethodCollection;
use Cicada\Core\Content\Category\CategoryCollection;
use Cicada\Core\Content\Cms\Aggregate\CmsBlock\CmsBlockCollection;
use Cicada\Core\Content\Cms\Aggregate\CmsSection\CmsSectionCollection;
use Cicada\Core\Content\MailTemplate\Aggregate\MailTemplateMedia\MailTemplateMediaCollection;
use Cicada\Core\Content\Media\Aggregate\MediaFolder\MediaFolderEntity;
use Cicada\Core\Content\Media\Aggregate\MediaThumbnail\MediaThumbnailCollection;
use Cicada\Core\Content\Media\Aggregate\MediaTranslation\MediaTranslationCollection;
use Cicada\Core\Content\Media\MediaType\MediaType;
use Cicada\Core\Content\Media\MediaType\SpatialObjectType;
use Cicada\Core\Content\Product\Aggregate\ProductConfiguratorSetting\ProductConfiguratorSettingCollection;
use Cicada\Core\Content\Product\Aggregate\ProductDownload\ProductDownloadCollection;
use Cicada\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerCollection;
use Cicada\Core\Content\Product\Aggregate\ProductMedia\ProductMediaCollection;
use Cicada\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionCollection;
use Cicada\Core\Framework\App\Aggregate\AppPaymentMethod\AppPaymentMethodCollection;
use Cicada\Core\Framework\App\Aggregate\AppShippingMethod\AppShippingMethodEntity;
use Cicada\Core\Framework\DataAbstractionLayer\Entity;
use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\DataAbstractionLayer\EntityCustomFieldsTrait;
use Cicada\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Tag\TagCollection;
use Cicada\Core\System\User\UserCollection;
use Cicada\Core\System\User\UserEntity;

/**
 * @phpstan-type MediaConfig array{'spatialObject': array{'arReady': bool}}
 */
#[Package('frontend')]
class MediaEntity extends Entity
{
    use EntityCustomFieldsTrait;
    use EntityIdTrait;

    /**
     * @var string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $userId;

    /**
     * @var string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $mimeType;

    /**
     * @var string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $fileExtension;

    /**
     * @var int|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $fileSize;

    /**
     * @var string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $title;

    /**
     * @var string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $metaDataRaw;

    /**
     * @internal
     *
     * @var string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $mediaTypeRaw;

    /**
     * @var array<string, mixed>|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $metaData;

    /**
     * @var MediaType|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $mediaType;

    /**
     * @var \DateTimeInterface|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $uploadedAt;

    /**
     * @var string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $alt;

    /**
     * @var string
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $url = '';

    /**
     * @var string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $fileName;

    /**
     * @var UserEntity|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $user;

    /**
     * @var MediaTranslationCollection|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $translations;

    /**
     * @var CategoryCollection|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $categories;

    /**
     * @var ProductManufacturerCollection|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $productManufacturers;

    /**
     * @var ProductMediaCollection|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $productMedia;

    /**
     * @var UserCollection|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $avatarUsers;

    /**
     * @var MediaThumbnailCollection|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $thumbnails;

    /**
     * @var string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $mediaFolderId;

    /**
     * @var MediaFolderEntity|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $mediaFolder;

    /**
     * @var bool
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $hasFile = false;

    /**
     * @var bool
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $private = false;

    /**
     * @var PropertyGroupOptionCollection|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $propertyGroupOptions;

    /**
     * @var MailTemplateMediaCollection|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $mailTemplateMedia;

    /**
     * @var TagCollection|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $tags;

    /**
     * @internal
     *
     * @var string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $thumbnailsRo;

    protected ?string $path = null;


    /**
     * @var CmsBlockCollection|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $cmsBlocks;

    /**
     * @var CmsSectionCollection|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $cmsSections;

    /**
     * @var CmsBlockCollection|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $cmsPages;


    /**
     * @experimental stableVersion:v6.7.0 feature:SPATIAL_BASES
     *
     * @var MediaConfig|null
     */
    protected ?array $config;

    public function get(string $property)
    {
        if ($property === 'hasFile') {
            return $this->hasFile();
        }

        return parent::get($property);
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function setUserId(string $userId): void
    {
        $this->userId = $userId;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): void
    {
        $this->mimeType = $mimeType;
    }

    public function getFileExtension(): ?string
    {
        return $this->fileExtension;
    }

    public function setFileExtension(string $fileExtension): void
    {
        $this->fileExtension = $fileExtension;
    }

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function setFileSize(int $fileSize): void
    {
        $this->fileSize = $fileSize;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getMetaData(): ?array
    {
        return $this->metaData;
    }

    /**
     * @param array<string, mixed> $metaData
     */
    public function setMetaData(array $metaData): void
    {
        $this->metaData = $metaData;
    }

    public function getMediaType(): ?MediaType
    {
        return $this->mediaType;
    }

    public function setMediaType(MediaType $mediaType): void
    {
        $this->mediaType = $mediaType;
    }

    public function getUploadedAt(): ?\DateTimeInterface
    {
        return $this->uploadedAt;
    }

    public function setUploadedAt(\DateTimeInterface $uploadedAt): void
    {
        $this->uploadedAt = $uploadedAt;
    }

    public function getAlt(): ?string
    {
        return $this->alt;
    }

    public function setAlt(string $alt): void
    {
        $this->alt = $alt;
    }

    public function getUser(): ?UserEntity
    {
        return $this->user;
    }

    public function setUser(UserEntity $user): void
    {
        $this->user = $user;
    }

    public function getTranslations(): ?MediaTranslationCollection
    {
        return $this->translations;
    }

    public function setTranslations(MediaTranslationCollection $translations): void
    {
        $this->translations = $translations;
    }

    public function getCategories(): ?CategoryCollection
    {
        return $this->categories;
    }

    public function setCategories(CategoryCollection $categories): void
    {
        $this->categories = $categories;
    }

    public function getAvatarUsers(): ?UserCollection
    {
        return $this->avatarUsers;
    }

    public function setAvatarUsers(UserCollection $avatarUsers): void
    {
        $this->avatarUsers = $avatarUsers;
    }

    public function getThumbnails(): ?MediaThumbnailCollection
    {
        return $this->thumbnails;
    }

    public function setThumbnails(MediaThumbnailCollection $thumbnailCollection): void
    {
        $this->thumbnails = $thumbnailCollection;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function hasFile(): bool
    {
        $hasFile = $this->mimeType !== null && $this->fileExtension !== null && $this->fileName !== null;

        return $this->hasFile = $hasFile || $this->path !== null;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function getFileNameIncludingExtension(): ?string
    {
        if ($this->fileName === null || $this->fileExtension === null) {
            return null;
        }

        return \sprintf('%s.%s', $this->fileName, $this->fileExtension);
    }

    public function setFileName(string $fileName): void
    {
        $this->fileName = $fileName;
    }

    public function getMediaFolderId(): ?string
    {
        return $this->mediaFolderId;
    }

    public function setMediaFolderId(string $mediaFolderId): void
    {
        $this->mediaFolderId = $mediaFolderId;
    }

    public function getMediaFolder(): ?MediaFolderEntity
    {
        return $this->mediaFolder;
    }

    public function setMediaFolder(MediaFolderEntity $mediaFolder): void
    {
        $this->mediaFolder = $mediaFolder;
    }


    public function getMetaDataRaw(): ?string
    {
        return $this->metaDataRaw;
    }

    public function setMetaDataRaw(string $metaDataRaw): void
    {
        $this->metaDataRaw = $metaDataRaw;
    }

    /**
     * @internal
     */
    public function getMediaTypeRaw(): ?string
    {
        $this->checkIfPropertyAccessIsAllowed('mediaTypeRaw');

        return $this->mediaTypeRaw;
    }

    /**
     * @internal
     */
    public function setMediaTypeRaw(string $mediaTypeRaw): void
    {
        $this->mediaTypeRaw = $mediaTypeRaw;
    }


    public function getTags(): ?TagCollection
    {
        return $this->tags;
    }

    public function setTags(TagCollection $tags): void
    {
        $this->tags = $tags;
    }

    /**
     * @internal
     */
    public function getThumbnailsRo(): ?string
    {
        $this->checkIfPropertyAccessIsAllowed('thumbnailsRo');

        return $this->thumbnailsRo;
    }

    /**
     * @internal
     */
    public function setThumbnailsRo(string $thumbnailsRo): void
    {
        $this->thumbnailsRo = $thumbnailsRo;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $data = parent::jsonSerialize();
        unset($data['metaDataRaw'], $data['mediaTypeRaw']);
        $data['hasFile'] = $this->hasFile();

        return $data;
    }

    public function getCmsBlocks(): ?CmsBlockCollection
    {
        return $this->cmsBlocks;
    }

    public function setCmsBlocks(CmsBlockCollection $cmsBlocks): void
    {
        $this->cmsBlocks = $cmsBlocks;
    }

    public function getCmsSections(): ?CmsSectionCollection
    {
        return $this->cmsSections;
    }

    public function setCmsSections(CmsSectionCollection $cmsSections): void
    {
        $this->cmsSections = $cmsSections;
    }

    public function getCmsPages(): ?CmsBlockCollection
    {
        return $this->cmsPages;
    }

    public function setCmsPages(CmsBlockCollection $cmsPages): void
    {
        $this->cmsPages = $cmsPages;
    }

    public function isPrivate(): bool
    {
        return $this->private;
    }

    public function setPrivate(bool $private): void
    {
        $this->private = $private;
    }

    public function hasPath(): bool
    {
        return $this->path !== null;
    }

    public function getPath(): string
    {
        return $this->path ?? '';
    }

    public function setPath(?string $path): void
    {
        $this->path = $path;
    }

    /**
     * @experimental stableVersion:v6.7.0 feature:SPATIAL_BASES
     *
     * @return MediaConfig|null
     */
    public function getConfig(): ?array
    {
        return $this->config;
    }

    /**
     * @experimental stableVersion:v6.7.0 feature:SPATIAL_BASES
     *
     * @param MediaConfig|null $configuration
     */
    public function setConfig(?array $configuration): void
    {
        $this->config = $configuration;
    }

    /**
     * @experimental stableVersion:v6.7.0 feature:SPATIAL_BASES
     */
    public function isSpatialObject(): bool
    {
        return $this->mediaType instanceof SpatialObjectType;
    }
}
