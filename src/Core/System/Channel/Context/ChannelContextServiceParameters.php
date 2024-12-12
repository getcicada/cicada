<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel\Context;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Struct;

#[Package('core')]
class ChannelContextServiceParameters extends Struct
{
    /**
     * @var string
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $channelId;

    /**
     * @var string
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $token;

    /**
     * @var string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $languageId;

    /**
     * @var string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $domainId;

    /**
     * @var Context|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $originalContext;

    public function __construct(
        string $channelId,
        string $token,
        ?string $languageId = null,
        ?string $domainId = null,
        ?Context $originalContext = null,
        protected ?string $imitatingUserId = null
    ) {
        $this->channelId = $channelId;
        $this->token = $token;
        $this->languageId = $languageId;
        $this->domainId = $domainId;
        $this->originalContext = $originalContext;
    }

    public function getChannelId(): string
    {
        return $this->channelId;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getLanguageId(): ?string
    {
        return $this->languageId;
    }

    public function getDomainId(): ?string
    {
        return $this->domainId;
    }

    public function getOriginalContext(): ?Context
    {
        return $this->originalContext;
    }

    public function getImitatingUserId(): ?string
    {
        return $this->imitatingUserId;
    }
}
