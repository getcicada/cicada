<?php declare(strict_types=1);

namespace Cicada\Core\System\SystemConfig;

use Cicada\Core\Framework\DataAbstractionLayer\Entity;
use Cicada\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelEntity;

#[Package('services-settings')]
class SystemConfigEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $configurationKey;

    /**
     * @var array|bool|float|int|string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $configurationValue;

    /**
     * @var string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $channelId;

    /**
     * @var ChannelEntity|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $channel;

    public function getConfigurationKey(): string
    {
        return $this->configurationKey;
    }

    public function setConfigurationKey(string $configurationKey): void
    {
        $this->configurationKey = $configurationKey;
    }

    /**
     * @return array|bool|float|int|string|null
     */
    public function getConfigurationValue()
    {
        return $this->configurationValue;
    }

    /**
     * @param array|bool|float|int|string|null $configurationValue
     */
    public function setConfigurationValue($configurationValue): void
    {
        $this->configurationValue = $configurationValue;
    }

    public function getChannelId(): ?string
    {
        return $this->channelId;
    }

    public function setChannelId(?string $channelId): void
    {
        $this->channelId = $channelId;
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
