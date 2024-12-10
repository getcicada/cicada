<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel\Context;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Util\Random;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\Channel\Event\ChannelContextTokenChangeEvent;
use Cicada\Core\System\Channel\ChannelContext;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Package('core')]
class ChannelContextPersister
{
    private readonly string $lifetimeInterval;

    /**
     * @internal
     */
    public function __construct(
        private readonly Connection $connection,
        private readonly EventDispatcherInterface $eventDispatcher,
        ?string $lifetimeInterval = 'P1D'
    ) {
        $this->lifetimeInterval = $lifetimeInterval ?? 'P1D';
    }

    /**
     * @param array<string, mixed> $newParameters
     */
    public function save(string $token, array $newParameters, string $channelId, ?string $memberId = null): void
    {
        $existing = $this->load($token, $channelId, $memberId);

        $parameters = array_replace_recursive($existing, $newParameters);
        if (isset($newParameters['permissions']) && $newParameters['permissions'] === []) {
            $parameters['permissions'] = [];
        }

        unset($parameters['token']);

        $this->connection->executeStatement(
            'REPLACE INTO channel_api_context (`token`, `payload`, `channel_id`, `member_id`, `updated_at`)
                VALUES (:token, :payload, :channelId, :memberId, :updatedAt)',
            [
                'token' => $token,
                'payload' => json_encode($parameters, \JSON_THROW_ON_ERROR),
                'channelId' => $channelId ? Uuid::fromHexToBytes($channelId) : null,
                'memberId' => $memberId ? Uuid::fromHexToBytes($memberId) : null,
                'updatedAt' => (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]
        );
    }

    public function delete(string $token, string $channelId, ?string $memberId = null): void
    {
        $this->connection->executeStatement(
            'DELETE FROM channel_api_context WHERE token = :token',
            [
                'token' => $token,
            ]
        );
    }

    public function replace(string $oldToken, ChannelContext $context): string
    {
        $newToken = Random::getAlphanumericString(32);

        $affected = $this->connection->executeStatement(
            'UPDATE `channel_api_context`
                   SET `token` = :newToken,
                       `updated_at` = :updatedAt
                   WHERE `token` = :oldToken',
            [
                'newToken' => $newToken,
                'oldToken' => $oldToken,
                'updatedAt' => (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]
        );

        if ($affected === 0) {
            $member = $context->getMember();

            $this->connection->insert('channel_api_context', [
                'token' => $newToken,
                'payload' => json_encode([]),
                'channel_id' => Uuid::fromHexToBytes($context->getChannel()->getId()),
                'member_id' => $member ? Uuid::fromHexToBytes($member->getId()) : null,
                'updated_at' => (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]);
        }

        $context->assign(['token' => $newToken]);
        $this->eventDispatcher->dispatch(new ChannelContextTokenChangeEvent($context, $oldToken, $newToken));

        return $newToken;
    }

    /**
     * @return array<string, mixed>
     */
    public function load(string $token, string $channelId, ?string $memberId = null): array
    {
        $qb = $this->connection->createQueryBuilder();

        $qb->select('*');
        $qb->from('channel_api_context');

        $qb->where('channel_id = :channelId');
        $qb->setParameter('channelId', Uuid::fromHexToBytes($channelId));

        if ($memberId !== null) {
            $qb->andWhere('(token = :token OR member_id = :memberId)');
            $qb->setParameter('token', $token);
            $qb->setParameter('memberId', Uuid::fromHexToBytes($memberId));
            $qb->setMaxResults(2);
        } else {
            $qb->andWhere('token = :token');
            $qb->setParameter('token', $token);
            $qb->setMaxResults(1);
        }

        $data = $qb->executeQuery()->fetchAllAssociative();

        if (empty($data)) {
            return [];
        }

        $memberContext = $channelId && $memberId ? $this->getMemberContext($data, $channelId, $memberId) : null;

        $context = $memberContext ?? array_shift($data);

        $updatedAt = new \DateTimeImmutable($context['updated_at']);
        $expiredTime = $updatedAt->add(new \DateInterval($this->lifetimeInterval));

        $payload = array_filter(json_decode((string) $context['payload'], true, 512, \JSON_THROW_ON_ERROR));
        $now = new \DateTimeImmutable();
        if ($expiredTime < $now) {
            // context is expired
            $payload = ['expired' => true];
        } else {
            $payload['expired'] = false;
        }

        $payload['token'] = $context['token'];

        return $payload;
    }

    public function revokeAllMemberTokens(string $memberId, string ...$preserveTokens): void
    {
        $revokeParams = [
            'memberId' => null,
            'billingAddressId' => null,
            'shippingAddressId' => null,
        ];

        $qb = $this->connection->createQueryBuilder();
        $qb
            ->update('channel_api_context')
            ->set('payload', ':payload')
            ->set('member_id', 'NULL')
            ->set('updated_at', ':updatedAt')
            ->where('member_id = :memberId')
            ->setParameter('updatedAt', (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT))
            ->setParameter('payload', json_encode($revokeParams))
            ->setParameter('memberId', Uuid::fromHexToBytes($memberId));

        // keep tokens valid, which are given in $preserveTokens
        if ($preserveTokens) {
            $qb
                ->andWhere($qb->expr()->notIn('token', ':preserveTokens'))
                ->setParameter('preserveTokens', $preserveTokens, ArrayParameterType::STRING);
        }

        $qb->executeStatement();
    }

    /**
     * @param array<array<string, mixed>> $data
     *
     * @return array<string, mixed>|null
     */
    private function getMemberContext(array $data, string $channelId, string $memberId): ?array
    {
        foreach ($data as $row) {
            if (!empty($row['member_id'])
                && Uuid::fromBytesToHex($row['channel_id']) === $channelId
                && Uuid::fromBytesToHex($row['member_id']) === $memberId
            ) {
                return $row;
            }
        }

        return null;
    }
}
