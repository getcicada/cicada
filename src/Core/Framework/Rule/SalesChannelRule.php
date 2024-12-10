<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Rule;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelDefinition;

#[Package('services-settings')]
class ChannelRule extends Rule
{
    final public const RULE_NAME = 'channel';

    /**
     * @internal
     *
     * @param list<string>|null $channelIds
     */
    public function __construct(
        protected string $operator = self::OPERATOR_EQ,
        protected ?array $channelIds = null
    ) {
        parent::__construct();
    }

    public function match(RuleScope $scope): bool
    {
        return RuleComparison::uuids([$scope->getChannelContext()->getChannel()->getId()], $this->channelIds, $this->operator);
    }

    public function getConstraints(): array
    {
        return [
            'channelIds' => RuleConstraints::uuids(),
            'operator' => RuleConstraints::uuidOperators(false),
        ];
    }

    public function getConfig(): RuleConfig
    {
        return (new RuleConfig())
            ->operatorSet(RuleConfig::OPERATOR_SET_STRING, false, true)
            ->entitySelectField('channelIds', ChannelDefinition::ENTITY_NAME, true);
    }
}
