<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel\Aggregate\ChannelTranslation;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCustomFieldsTrait;
use Cicada\Core\Framework\DataAbstractionLayer\TranslationEntity;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelEntity;

#[Package('frontend')]
class ChannelTranslationEntity extends TranslationEntity
{
    use EntityCustomFieldsTrait;

    /**
     * @var string
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $channelId;

    /**
     * @var string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $name;

    /**
     * @var array|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $homeSlotConfig;

    /**
     * @var bool
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $homeEnabled;

    /**
     * @var string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $homeName;

    /**
     * @var string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $homeMetaTitle;

    /**
     * @var string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $homeMetaDescription;

    /**
     * @var string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $homeKeywords;

    /**
     * @var ChannelEntity|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $channel;

    public function getChannelId(): string
    {
        return $this->channelId;
    }

    public function setChannelId(string $channelId): void
    {
        $this->channelId = $channelId;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getHomeSlotConfig(): ?array
    {
        return $this->homeSlotConfig;
    }

    public function setHomeSlotConfig(?array $homeSlotConfig): void
    {
        $this->homeSlotConfig = $homeSlotConfig;
    }

    public function getHomeEnabled(): ?bool
    {
        return $this->homeEnabled;
    }

    public function setHomeEnabled(bool $homeEnabled): void
    {
        $this->homeEnabled = $homeEnabled;
    }

    public function getHomeName(): ?string
    {
        return $this->homeName;
    }

    public function setHomeName(?string $homeName): void
    {
        $this->homeName = $homeName;
    }

    public function getHomeMetaTitle(): ?string
    {
        return $this->homeMetaTitle;
    }

    public function setHomeMetaTitle(?string $homeMetaTitle): void
    {
        $this->homeMetaTitle = $homeMetaTitle;
    }

    public function getHomeMetaDescription(): ?string
    {
        return $this->homeMetaDescription;
    }

    public function setHomeMetaDescription(?string $homeMetaDescription): void
    {
        $this->homeMetaDescription = $homeMetaDescription;
    }

    public function getHomeKeywords(): ?string
    {
        return $this->homeKeywords;
    }

    public function setHomeKeywords(?string $homeKeywords): void
    {
        $this->homeKeywords = $homeKeywords;
    }

    public function getChannel(): ?ChannelEntity
    {
        return $this->channel;
    }

    public function setChannel(ChannelEntity $channel): void
    {
        $this->channel = $channel;
    }
}
