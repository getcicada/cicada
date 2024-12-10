<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel;

use Cicada\Frontend\Member\Aggregate\MemberGroup\MemberGroupEntity;
use Cicada\Frontend\Member\MemberEntity;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Pricing\CashRoundingConfig;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\StateAwareTrait;
use Cicada\Core\Framework\Struct\Struct;
use Cicada\Core\System\Channel\Exception\ContextPermissionsLockedException;
#[Package('core')]
class ChannelContext extends Struct
{
    use StateAwareTrait;

    /**
     * Unique token for context, e.g. stored in session or provided in request headers
     *
     */
    protected string $token;

    protected ChannelEntity $channel;


    protected array $permissions;

    protected bool $permisionsLocked = false;

    protected ?string $imitatingUserId;

    protected Context $context;

    /**
     * @internal
     *
     * @param array<string, string[]> $areaRuleIds
     */
    public function __construct(
        Context $baseContext,
        string $token,
        private ?string $domainId,
        ChannelEntity $channel,
        protected array $areaRuleIds = []
    ) {
        $this->channel = $channel;
        $this->token = $token;
        $this->context = $baseContext;
        $this->imitatingUserId = null;
    }

    public function getChannel(): ChannelEntity
    {
        return $this->channel;
    }
    public function getContext(): Context
    {
        return $this->context;
    }

    /**
     * @return string[]
     */
    public function getRuleIds(): array
    {
        return $this->getContext()->getRuleIds();
    }

    /**
     * @param array<string> $ruleIds
     */
    public function setRuleIds(array $ruleIds): void
    {
        $this->getContext()->setRuleIds($ruleIds);
    }

    /**
     * @internal
     *
     * @return array<string, string[]>
     */
    public function getAreaRuleIds(): array
    {
        return $this->areaRuleIds;
    }

    /**
     * @internal
     *
     * @param string[] $areas
     *
     * @return string[]
     */
    public function getRuleIdsByAreas(array $areas): array
    {
        $ruleIds = [];

        foreach ($areas as $area) {
            if (empty($this->areaRuleIds[$area])) {
                continue;
            }

            $ruleIds = array_unique(array_merge($ruleIds, $this->areaRuleIds[$area]));
        }

        return array_values($ruleIds);
    }

    /**
     * @internal
     *
     * @param array<string, string[]> $areaRuleIds
     */
    public function setAreaRuleIds(array $areaRuleIds): void
    {
        $this->areaRuleIds = $areaRuleIds;
    }

    public function lockRules(): void
    {
        $this->getContext()->lockRules();
    }

    public function lockPermissions(): void
    {
        $this->permisionsLocked = true;
    }

    public function getToken(): string
    {
        return $this->token;
    }
    /**
     * @return array<string, bool>
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }

    /**
     * @param array<string, bool> $permissions
     */
    public function setPermissions(array $permissions): void
    {
        if ($this->permisionsLocked) {
            throw new ContextPermissionsLockedException();
        }

        $this->permissions = array_filter($permissions);
    }

    public function getApiAlias(): string
    {
        return 'channel_context';
    }

    public function hasPermission(string $permission): bool
    {
        return \array_key_exists($permission, $this->permissions) && $this->permissions[$permission];
    }

    public function getChannelId(): string
    {
        return $this->getChannel()->getId();
    }

    public function addState(string ...$states): void
    {
        $this->context->addState(...$states);
    }

    public function removeState(string $state): void
    {
        $this->context->removeState($state);
    }

    public function hasState(string ...$states): bool
    {
        return $this->context->hasState(...$states);
    }

    /**
     * @return string[]
     */
    public function getStates(): array
    {
        return $this->context->getStates();
    }

    public function getDomainId(): ?string
    {
        return $this->domainId;
    }

    public function setDomainId(?string $domainId): void
    {
        $this->domainId = $domainId;
    }

    /**
     * @return string[]
     */
    public function getLanguageIdChain(): array
    {
        return $this->context->getLanguageIdChain();
    }

    public function getLanguageId(): string
    {
        return $this->context->getLanguageId();
    }

    public function getVersionId(): string
    {
        return $this->context->getVersionId();
    }

    public function considerInheritance(): bool
    {
        return $this->context->considerInheritance();
    }

    public function getImitatingUserId(): ?string
    {
        return $this->imitatingUserId;
    }

    public function setImitatingUserId(?string $imitatingUserId): void
    {
        $this->imitatingUserId = $imitatingUserId;
    }

    /**
     * @template TReturn of mixed
     *
     * @param callable(ChannelContext): TReturn $callback
     *
     * @return TReturn the return value of the provided callback function
     */
    public function live(callable $callback): mixed
    {
        $before = $this->context;

        $this->context = $this->context->createWithVersionId(Defaults::LIVE_VERSION);

        $result = $callback($this);

        $this->context = $before;

        return $result;
    }
}
