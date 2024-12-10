<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel\Aggregate\ChannelType;

use Cicada\Core\Framework\DataAbstractionLayer\Entity;
use Cicada\Core\Framework\DataAbstractionLayer\EntityCustomFieldsTrait;
use Cicada\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\Aggregate\ChannelTypeTranslation\ChannelTypeTranslationCollection;
use Cicada\Core\System\Channel\ChannelCollection;

#[Package('frontend')]
class ChannelTypeEntity extends Entity
{
    use EntityCustomFieldsTrait;
    use EntityIdTrait;

    /**
     * @var string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $name;

    /**
     * @var string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $manufacturer;

    /**
     * @var string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $description;

    /**
     * @var string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $descriptionLong;

    /**
     * @var string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $coverUrl;

    /**
     * @var string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $iconName;

    /**
     * @var array|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $screenshotUrls;

    /**
     * @var ChannelCollection|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $channels;

    /**
     * @var ChannelTypeTranslationCollection|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $translations;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getManufacturer(): ?string
    {
        return $this->manufacturer;
    }

    public function setManufacturer(?string $manufacturer): void
    {
        $this->manufacturer = $manufacturer;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getDescriptionLong(): ?string
    {
        return $this->descriptionLong;
    }

    public function setDescriptionLong(?string $descriptionLong): void
    {
        $this->descriptionLong = $descriptionLong;
    }

    public function getCoverUrl(): string
    {
        return $this->coverUrl;
    }

    public function setCoverUrl(string $coverUrl): void
    {
        $this->coverUrl = $coverUrl;
    }

    public function getIconName(): string
    {
        return $this->iconName;
    }

    public function setIconName(string $iconName): void
    {
        $this->iconName = $iconName;
    }

    public function getScreenshotUrls(): array
    {
        return $this->screenshotUrls;
    }

    public function setScreenshotUrls(array $screenshotUrls): void
    {
        $this->screenshotUrls = $screenshotUrls;
    }

    public function getChannels(): ?ChannelCollection
    {
        return $this->channels;
    }

    public function setChannels(ChannelCollection $channels): void
    {
        $this->channels = $channels;
    }

    public function getTranslations(): ?ChannelTypeTranslationCollection
    {
        return $this->translations;
    }

    public function setTranslations(ChannelTypeTranslationCollection $translations): void
    {
        $this->translations = $translations;
    }
}
