<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel\Validation;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\DeleteCommand;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\InsertCommand;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\UpdateCommand;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\WriteCommand;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Validation\PreWriteValidationEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Validation\WriteConstraintViolationException;
use Cicada\Core\System\Channel\Aggregate\ChannelLanguage\ChannelLanguageDefinition;
use Cicada\Core\System\Channel\ChannelDefinition;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * @internal
 *
 * @phpstan-type Mapping array<string, array{current_default: string, new_default: string, inserts: list<string>, updateId: string, deletions: list<string>, state: list<string>}>
 */
#[Package('frontend')]
class ChannelValidator implements EventSubscriberInterface
{
    private const INSERT_VALIDATION_MESSAGE = 'The sales channel with id "%s" does not have a default sales channel language id in the language list.';
    private const INSERT_VALIDATION_CODE = 'SYSTEM__NO_GIVEN_DEFAULT_LANGUAGE_ID';

    private const DUPLICATED_ENTRY_VALIDATION_MESSAGE = 'The sales channel language "%s" for the sales channel "%s" already exists.';
    private const DUPLICATED_ENTRY_VALIDATION_CODE = 'SYSTEM__DUPLICATED_SALES_CHANNEL_LANGUAGE';

    private const UPDATE_VALIDATION_MESSAGE = 'Cannot update default language id because the given id is not in the language list of sales channel with id "%s"';
    private const UPDATE_VALIDATION_CODE = 'SYSTEM__CANNOT_UPDATE_DEFAULT_LANGUAGE_ID';

    private const DELETE_VALIDATION_MESSAGE = 'Cannot delete default language id from language list of the sales channel with id "%s".';
    private const DELETE_VALIDATION_CODE = 'SYSTEM__CANNOT_DELETE_DEFAULT_LANGUAGE_ID';

    /**
     * @internal
     */
    public function __construct(private readonly Connection $connection)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PreWriteValidationEvent::class => 'handleChannelLanguageIds',
        ];
    }

    public function handleChannelLanguageIds(PreWriteValidationEvent $event): void
    {
        $mapping = $this->extractMapping($event);

        if (!$mapping) {
            return;
        }

        $channelIds = array_keys($mapping);
        $states = $this->fetchCurrentLanguageStates($channelIds);

        $mapping = $this->mergeCurrentStatesWithMapping($mapping, $states);

        $this->validateLanguages($mapping, $event);
    }

    /**
     * Build a key map with the following data structure:
     *
     * 'channel_id' => [
     *     'current_default' => 'en',
     *     'new_default' => 'de',
     *     'inserts' => ['de', 'en'],
     *     'updateId' => 'de',
     *     'deletions' => ['gb'],
     *     'state' => ['en', 'gb']
     * ]
     *
     * @return Mapping
     */
    private function extractMapping(PreWriteValidationEvent $event): array
    {
        $mapping = [];
        foreach ($event->getCommands() as $command) {
            if ($command->getEntityName() === ChannelDefinition::ENTITY_NAME) {
                $this->handleChannelMapping($mapping, $command);

                continue;
            }

            if ($command->getEntityName() === ChannelLanguageDefinition::ENTITY_NAME) {
                $this->handleChannelLanguageMapping($mapping, $command);
            }
        }

        return $mapping;
    }

    /**
     * @param Mapping $mapping
     */
    private function handleChannelMapping(array &$mapping, WriteCommand $command): void
    {
        if (!isset($command->getPayload()['language_id'])) {
            return;
        }

        if ($command instanceof UpdateCommand) {
            $id = Uuid::fromBytesToHex($command->getPrimaryKey()['id']);
            $mapping[$id]['updateId'] = Uuid::fromBytesToHex($command->getPayload()['language_id']);

            return;
        }

        if (!$command instanceof InsertCommand || !$this->isSupportedChannelType($command)) {
            return;
        }

        $id = Uuid::fromBytesToHex($command->getPrimaryKey()['id']);
        $mapping[$id]['new_default'] = Uuid::fromBytesToHex($command->getPayload()['language_id']);
        $mapping[$id]['inserts'] = [];
        $mapping[$id]['state'] = [];
    }

    private function isSupportedChannelType(WriteCommand $command): bool
    {
        $typeId = Uuid::fromBytesToHex($command->getPayload()['type_id']);

        return $typeId === Defaults::CHANNEL_TYPE_WEB
            || $typeId === Defaults::CHANNEL_TYPE_API;
    }

    /**
     * @param Mapping $mapping
     */
    private function handleChannelLanguageMapping(array &$mapping, WriteCommand $command): void
    {
        $language = Uuid::fromBytesToHex($command->getPrimaryKey()['language_id']);
        $id = Uuid::fromBytesToHex($command->getPrimaryKey()['channel_id']);
        $mapping[$id]['state'] = [];

        if ($command instanceof DeleteCommand) {
            $mapping[$id]['deletions'][] = $language;

            return;
        }

        if ($command instanceof InsertCommand) {
            $mapping[$id]['inserts'][] = $language;
        }
    }

    /**
     * @param array<string, array<string, list<string>>> $mapping
     */
    private function validateLanguages(array $mapping, PreWriteValidationEvent $event): void
    {
        $inserts = [];
        $duplicates = [];
        $deletions = [];
        $updates = [];

        foreach ($mapping as $id => $channel) {
            if (isset($channel['inserts'])) {
                if (!$this->validInsertCase($channel)) {
                    $inserts[$id] = $channel['new_default'];
                }

                $duplicatedIds = $this->getDuplicates($channel);

                if ($duplicatedIds) {
                    $duplicates[$id] = $duplicatedIds;
                }
            }

            if (isset($channel['deletions']) && !$this->validDeleteCase($channel)) {
                $deletions[$id] = $channel['current_default'];
            }

            if (isset($channel['updateId']) && !$this->validUpdateCase($channel)) {
                $updates[$id] = $channel['updateId'];
            }
        }

        $this->writeInsertViolationExceptions($inserts, $event);
        $this->writeDuplicateViolationExceptions($duplicates, $event);
        $this->writeDeleteViolationExceptions($deletions, $event);
        $this->writeUpdateViolationExceptions($updates, $event);
    }

    /**
     * @param array<string, mixed> $channel
     */
    private function validInsertCase(array $channel): bool
    {
        return empty($channel['new_default'])
            || \in_array($channel['new_default'], $channel['inserts'], true);
    }

    /**
     * @param array<string, mixed> $channel
     */
    private function validUpdateCase(array $channel): bool
    {
        $updateId = $channel['updateId'];

        return \in_array($updateId, $channel['state'], true)
            || empty($channel['new_default']) && $updateId === $channel['current_default']
            || isset($channel['inserts']) && \in_array($updateId, $channel['inserts'], true);
    }

    /**
     * @param array<string, mixed> $channel
     */
    private function validDeleteCase(array $channel): bool
    {
        return !\in_array($channel['current_default'], $channel['deletions'], true);
    }

    /**
     * @param array<string, list<string>> $channel
     *
     * @return list<string>
     */
    private function getDuplicates(array $channel): array
    {
        return array_values(array_intersect($channel['state'], $channel['inserts']));
    }

    /**
     * @param array<string, mixed> $inserts
     */
    private function writeInsertViolationExceptions(array $inserts, PreWriteValidationEvent $event): void
    {
        if (!$inserts) {
            return;
        }

        $violations = new ConstraintViolationList();
        $channelIds = array_keys($inserts);

        foreach ($channelIds as $id) {
            $violations->add(new ConstraintViolation(
                \sprintf(self::INSERT_VALIDATION_MESSAGE, $id),
                \sprintf(self::INSERT_VALIDATION_MESSAGE, '{{ channelId }}'),
                ['{{ channelId }}' => $id],
                null,
                '/',
                null,
                null,
                self::INSERT_VALIDATION_CODE
            ));
        }

        $this->writeViolationException($violations, $event);
    }

    /**
     * @param array<string, list<string>> $duplicates
     */
    private function writeDuplicateViolationExceptions(array $duplicates, PreWriteValidationEvent $event): void
    {
        if (!$duplicates) {
            return;
        }

        $violations = new ConstraintViolationList();

        foreach ($duplicates as $id => $duplicateLanguages) {
            foreach ($duplicateLanguages as $languageId) {
                $violations->add(new ConstraintViolation(
                    \sprintf(self::DUPLICATED_ENTRY_VALIDATION_MESSAGE, $languageId, $id),
                    \sprintf(self::DUPLICATED_ENTRY_VALIDATION_MESSAGE, '{{ languageId }}', '{{ channelId }}'),
                    [
                        '{{ channelId }}' => $id,
                        '{{ languageId }}' => $languageId,
                    ],
                    null,
                    '/',
                    null,
                    null,
                    self::DUPLICATED_ENTRY_VALIDATION_CODE
                ));
            }
        }

        $this->writeViolationException($violations, $event);
    }

    /**
     * @param array<string, mixed> $deletions
     */
    private function writeDeleteViolationExceptions(array $deletions, PreWriteValidationEvent $event): void
    {
        if (!$deletions) {
            return;
        }

        $violations = new ConstraintViolationList();
        $channelIds = array_keys($deletions);

        foreach ($channelIds as $id) {
            $violations->add(new ConstraintViolation(
                \sprintf(self::DELETE_VALIDATION_MESSAGE, $id),
                \sprintf(self::DELETE_VALIDATION_MESSAGE, '{{ channelId }}'),
                ['{{ channelId }}' => $id],
                null,
                '/',
                null,
                null,
                self::DELETE_VALIDATION_CODE
            ));
        }

        $this->writeViolationException($violations, $event);
    }

    /**
     * @param array<string, mixed> $updates
     */
    private function writeUpdateViolationExceptions(array $updates, PreWriteValidationEvent $event): void
    {
        if (!$updates) {
            return;
        }

        $violations = new ConstraintViolationList();
        $channelIds = array_keys($updates);

        foreach ($channelIds as $id) {
            $violations->add(new ConstraintViolation(
                \sprintf(self::UPDATE_VALIDATION_MESSAGE, $id),
                \sprintf(self::UPDATE_VALIDATION_MESSAGE, '{{ channelId }}'),
                ['{{ channelId }}' => $id],
                null,
                '/',
                null,
                null,
                self::UPDATE_VALIDATION_CODE
            ));
        }

        $this->writeViolationException($violations, $event);
    }

    /**
     * @param array<string> $channelIds
     *
     * @return array<string, string>
     */
    private function fetchCurrentLanguageStates(array $channelIds): array
    {
        /** @var array<string, mixed> $result */
        $result = $this->connection->fetchAllAssociative(
            'SELECT LOWER(HEX(channel.id)) AS channel_id,
            LOWER(HEX(channel.language_id)) AS current_default,
            LOWER(HEX(mapping.language_id)) AS language_id
            FROM channel
            LEFT JOIN channel_language mapping
                ON mapping.channel_id = channel.id
                WHERE channel.id IN (:ids)',
            ['ids' => Uuid::fromHexToBytesList($channelIds)],
            ['ids' => ArrayParameterType::BINARY]
        );

        return $result;
    }

    /**
     * @param array<string, mixed> $mapping
     * @param array<string, mixed> $states
     *
     * @return array<string, mixed>
     */
    private function mergeCurrentStatesWithMapping(array $mapping, array $states): array
    {
        foreach ($states as $record) {
            $id = (string) $record['channel_id'];
            $mapping[$id]['current_default'] = $record['current_default'];
            $mapping[$id]['state'][] = $record['language_id'];
            $mapping[$id]['inserts'] = array_filter(
                $mapping[$id]['inserts'] ?? [],
                fn ($value) => $value !== $record['language_id']
            );
            if (empty($mapping[$id]['inserts'])) {
                unset($mapping[$id]['inserts']);
            }
        }

        return $mapping;
    }

    private function writeViolationException(ConstraintViolationList $violations, PreWriteValidationEvent $event): void
    {
        $event->getExceptions()->add(new WriteConstraintViolationException($violations));
    }
}
