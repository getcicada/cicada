<?php declare(strict_types=1);

namespace Cicada\Core\Framework;

use Cicada\Core\Defaults;
use Cicada\Core\Framework\Api\Context\AdminApiSource;
use Cicada\Core\Framework\Api\Context\ContextSource;
use Cicada\Core\Framework\Api\Context\SystemSource;
use Cicada\Core\Framework\DataAbstractionLayer\Pricing\CashRoundingConfig;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\StateAwareTrait;
use Cicada\Core\Framework\Struct\Struct;
use Cicada\Core\System\Channel\Exception\ContextRulesLockedException;
use Symfony\Component\Serializer\Annotation\Ignore;

#[Package('core')]
class Context extends Struct
{
    use StateAwareTrait;

    final public const SYSTEM_SCOPE = 'system';
    final public const USER_SCOPE = 'user';
    final public const CRUD_API_SCOPE = 'crud';

    final public const SKIP_TRIGGER_FLOW = 'skipTriggerFlow';

    /**
     * @var non-empty-array<string>
     */
    protected array $languageIdChain;

    protected string $scope = self::USER_SCOPE;

    protected bool $rulesLocked = false;

    /**
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    #[Ignore]
    protected $extensions = [];

    /**
     * @param array<string> $languageIdChain
     */
    public function __construct(
        protected ContextSource $source,
        array $languageIdChain = [Defaults::LANGUAGE_SYSTEM],
        protected string $versionId = Defaults::LIVE_VERSION,
        protected bool $considerInheritance = false,
    ) {
        if ($source instanceof SystemSource) {
            $this->scope = self::SYSTEM_SCOPE;
        }

        if (empty($languageIdChain)) {
            throw new \InvalidArgumentException('Argument languageIdChain must not be empty');
        }

        /** @var non-empty-array<string> $chain */
        $chain = array_keys(array_flip(array_filter($languageIdChain)));
        $this->languageIdChain = $chain;
    }

    /**
     * @internal
     */
    public static function createDefaultContext(?ContextSource $source = null): self
    {
        $source ??= new SystemSource();

        return new self($source);
    }

    public static function createCLIContext(?ContextSource $source = null): self
    {
        return self::createDefaultContext($source);
    }

    public function getSource(): ContextSource
    {
        return $this->source;
    }

    public function getVersionId(): string
    {
        return $this->versionId;
    }

    public function getLanguageId(): string
    {
        return $this->languageIdChain[0];
    }

    /**
     * @return array<string>
     */
    public function getRuleIds(): array
    {
        return $this->ruleIds;
    }

    /**
     * @return non-empty-array<string>
     */
    public function getLanguageIdChain(): array
    {
        return $this->languageIdChain;
    }

    public function createWithVersionId(string $versionId): self
    {
        $context = new self(
            $this->source,
            $this->ruleIds,
            $this->languageIdChain,
            $versionId,
            $this->considerInheritance,
        );
        $context->scope = $this->scope;

        foreach ($this->getExtensions() as $key => $extension) {
            $context->addExtension($key, $extension);
        }

        return $context;
    }

    /**
     * @template TReturn of mixed
     *
     * @param \Closure(Context): TReturn $callback
     *
     * @return TReturn the return value of the provided callback function
     */
    public function scope(string $scope, \Closure $callback)
    {
        $currentScope = $this->getScope();
        $this->scope = $scope;

        try {
            $result = $callback($this);
        } finally {
            $this->scope = $currentScope;
        }

        return $result;
    }

    public function getScope(): string
    {
        return $this->scope;
    }

    public function considerInheritance(): bool
    {
        return $this->considerInheritance;
    }

    public function setConsiderInheritance(bool $considerInheritance): void
    {
        $this->considerInheritance = $considerInheritance;
    }


    public function isAllowed(string $privilege): bool
    {
        if ($this->source instanceof AdminApiSource) {
            return $this->source->isAllowed($privilege);
        }

        return true;
    }

    /**
     * @param array<string> $ruleIds
     */
    public function setRuleIds(array $ruleIds): void
    {
        if ($this->rulesLocked) {
            throw new ContextRulesLockedException();
        }

        $this->ruleIds = array_filter(array_values($ruleIds));
    }

    /**
     * @template TReturn of mixed
     *
     * @param \Closure(Context): TReturn $function
     *
     * @return TReturn
     */
    public function enableInheritance(\Closure $function)
    {
        $previous = $this->considerInheritance;
        $this->considerInheritance = true;
        $result = $function($this);
        $this->considerInheritance = $previous;

        return $result;
    }

    /**
     * @template TReturn of mixed
     *
     * @param \Closure(Context): TReturn $function
     *
     * @return TReturn
     */
    public function disableInheritance(\Closure $function)
    {
        $previous = $this->considerInheritance;
        $this->considerInheritance = false;
        $result = $function($this);
        $this->considerInheritance = $previous;

        return $result;
    }

    public function getApiAlias(): string
    {
        return 'context';
    }
    public function lockRules(): void
    {
        $this->rulesLocked = true;
    }
}
