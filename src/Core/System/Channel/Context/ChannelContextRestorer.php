<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel\Context;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\Channel\ChannelContext;
use Cicada\Core\System\Channel\ChannelException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Package('core')]
class ChannelContextRestorer
{
    /**
     * @internal
     */
    public function __construct(
        private readonly AbstractChannelContextFactory $factory,
        private readonly Connection $connection,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * @param array<string> $overrideOptions
     *
     * @throws Exception
     */
    public function restoreByMember(string $memberId, Context $context, array $overrideOptions = []): ChannelContext
    {
        $member = $this->connection->createQueryBuilder()
            ->select(
                'LOWER(HEX(language_id))',
                'LOWER(HEX(member_group_id))',
                'LOWER(HEX(channel_id))',
            )
            ->from('member')
            ->where('id = :id')
            ->setParameter('id', Uuid::fromHexToBytes($memberId))
            ->executeQuery()
            ->fetchAssociative();

        if (!$member) {
            throw ChannelException::memberNotFoundByIdException($memberId);
        }

        [$languageId, $groupId, $channelId] = array_values($member);
        $options = [
            ChannelContextService::LANGUAGE_ID => $languageId,
            ChannelContextService::MEMBER_ID => $memberId,
            ChannelContextService::MEMBER_GROUP_ID => $groupId,
            ChannelContextService::VERSION_ID => $context->getVersionId(),
        ];

        $options = array_merge($options, $overrideOptions);

        $token = Uuid::randomHex();
        $channelContext = $this->factory->create(
            $token,
            $channelId,
            $options
        );

        $channelContext->getContext()->addState(...$context->getStates());

        return $channelContext;
    }
}
