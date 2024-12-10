<?php declare(strict_types=1);

namespace Cicada\Core\System\Snippet\Aggregate\SnippetSet;

use Cicada\Core\Framework\DataAbstractionLayer\Entity;
use Cicada\Core\Framework\DataAbstractionLayer\EntityCustomFieldsTrait;
use Cicada\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\Aggregate\ChannelDomain\ChannelDomainCollection;
use Cicada\Core\System\Snippet\SnippetCollection;

#[Package('services-settings')]
class SnippetSetEntity extends Entity
{
    use EntityCustomFieldsTrait;
    use EntityIdTrait;

    /**
     * @var string
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $name;

    /**
     * @var string
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $baseFile;

    /**
     * @var string
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $iso;

    /**
     * @var SnippetCollection|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $snippets;

    /**
     * @var ChannelDomainCollection|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $channelDomains;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getBaseFile(): string
    {
        return $this->baseFile;
    }

    public function setBaseFile(string $baseFile): void
    {
        $this->baseFile = $baseFile;
    }

    public function getIso(): string
    {
        return $this->iso;
    }

    public function setIso(string $iso): void
    {
        $this->iso = $iso;
    }

    public function getSnippets(): ?SnippetCollection
    {
        return $this->snippets;
    }

    public function setSnippets(SnippetCollection $snippets): void
    {
        $this->snippets = $snippets;
    }

    public function getChannelDomains(): ?ChannelDomainCollection
    {
        return $this->channelDomains;
    }

    public function setChannelDomains(ChannelDomainCollection $channelDomains): void
    {
        $this->channelDomains = $channelDomains;
    }
}
