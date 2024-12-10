<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel\Context;

use Cicada\Core\Defaults;
use Cicada\Core\Framework\Api\Context\AdminChannelApiSource;
use Cicada\Core\Framework\Api\Context\ChannelApiSource;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\Channel\BaseContext;
use Cicada\Core\System\Channel\ChannelEntity;
use Cicada\Core\System\Channel\ChannelException;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('core')]
class BaseContextFactory extends AbstractBaseContextFactory
{
    public function __construct(
        private readonly EntityRepository $channelRepository,
        private readonly Connection $connection,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function create(string $channelId, array $options = []): BaseContext
    {
        $context = $this->getContext($channelId, $options);

        $criteria = new Criteria([$channelId]);
        $criteria->setTitle('base-context-factory::sales-channel');
        $criteria->addAssociation('currency');
        $criteria->addAssociation('domains');

        $channel = $this->channelRepository->search($criteria, $context)->get($channelId);

        if (!$channel instanceof ChannelEntity) {
            throw ChannelException::channelNotFound($channelId);
        }

        $groupId = $channel->getMemberGroupId();

        $criteria = new Criteria([$channel->getMemberGroupId()]);
        $criteria->setTitle('base-context-factory::member-group');

        $context = new Context(
            $context->getSource(),
            [],
            $context->getLanguageIdChain(),
            $context->getVersionId(),
            true,
        );

        return new BaseContext(
            $context,
            $channel,
        );
    }

    /**
     * @param array<string, mixed> $session
     */
    private function getContext(string $channelId, array $session): Context
    {
        $sql = '
        # context-factory::base-context

        SELECT
          channel.id as channel_id,
          channel.language_id as channel_default_language_id,
          GROUP_CONCAT(LOWER(HEX(channel_language.language_id))) as channel_language_ids
        FROM channel
            LEFT JOIN channel_language
                ON channel_language.channel_id = channel.id
        WHERE channel.id = :id
        GROUP BY channel.id, channel.language_id';

        $data = $this->connection->fetchAssociative($sql, [
            'id' => Uuid::fromHexToBytes($channelId),
        ]);
        if ($data === false) {
            throw ChannelException::noContextData($channelId);
        }

        if (isset($session[ChannelContextService::ORIGINAL_CONTEXT])) {
            $origin = new AdminChannelApiSource($channelId, $session[ChannelContextService::ORIGINAL_CONTEXT]);
        } else {
            $origin = new ChannelApiSource($channelId);
        }

        // explode all available languages for the provided sales channel
        $languageIds = $data['channel_language_ids'] ? explode(',', (string) $data['channel_language_ids']) : [];
        $languageIds = array_keys(array_flip($languageIds));

        // check which language should be used in the current request (request header set, or context already contains a language - stored in `channel_api_context`)
        $defaultLanguageId = Uuid::fromBytesToHex($data['channel_default_language_id']);

        $languageChain = $this->buildLanguageChain($session, $defaultLanguageId, $languageIds);

        $versionId = Defaults::LIVE_VERSION;
        if (isset($session[ChannelContextService::VERSION_ID])) {
            $versionId = $session[ChannelContextService::VERSION_ID];
        }

        return new Context(
            $origin,
            [],
            $languageChain,
            $versionId,
            true
        );
    }
    /**
     * @param array<string, mixed> $sessionOptions
     * @param array<string> $availableLanguageIds
     *
     * @return non-empty-array<string>
     */
    private function buildLanguageChain(array $sessionOptions, string $defaultLanguageId, array $availableLanguageIds): array
    {
        $current = $sessionOptions[ChannelContextService::LANGUAGE_ID] ?? $defaultLanguageId;

        if (!\is_string($current) || !Uuid::isValid($current)) {
            throw ChannelException::invalidLanguageId();
        }

        // check provided language is part of the available languages
        if (!\in_array($current, $availableLanguageIds, true)) {
            throw ChannelException::providedLanguageNotAvailable($current, $availableLanguageIds);
        }

        if ($current === Defaults::LANGUAGE_SYSTEM) {
            return [Defaults::LANGUAGE_SYSTEM];
        }

        // provided language can be a child language
        return array_filter([$current, $this->getParentLanguageId($current), Defaults::LANGUAGE_SYSTEM]);
    }
    private function getParentLanguageId(string $languageId): ?string
    {
        $data = $this->connection->createQueryBuilder()
            ->select('LOWER(HEX(language.parent_id))')
            ->from('language')
            ->where('language.id = :id')
            ->setParameter('id', Uuid::fromHexToBytes($languageId))
            ->executeQuery()
            ->fetchOne();

        if ($data === false) {
            throw ChannelException::languageNotFound($languageId);
        }

        return $data;
    }
}
