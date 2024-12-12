<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_6;

use Cicada\Core\Content\Category\CategoryDefinition;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Api\Util\AccessKeyHelper;
use Cicada\Core\Framework\DataAbstractionLayer\Doctrine\MultiInsertQueryQueue;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Cicada\Core\Framework\Uuid\Uuid;
use Doctrine\DBAL\Connection;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
class Migration1536233560BasicData extends MigrationStep
{
    private ?string $enUsLanguageId = null;

    public function getCreationTimestamp(): int
    {
        return 1536233560;
    }
    public function update(Connection $connection): void
    {
        $hasData = $connection->executeQuery('SELECT 1 FROM `language` LIMIT 1')->fetchAssociative();
        if ($hasData) {
            return;
        }
        $this->createLanguage($connection);
        $this->createRootCategory($connection);
        $this->createChannelTypes($connection);
        $this->createChannel($connection);
        $this->createDefaultSnippetSets($connection);
        $this->createSystemConfigOptions($connection);

    }
    private function createDefaultSnippetSets(Connection $connection): void
    {
        $queue = new MultiInsertQueryQueue($connection);

        $queue->addInsert('snippet_set', ['id' => Uuid::randomBytes(), 'name' => 'BASE zh-CN', 'base_file' => 'messages.zh-CN', 'iso' => 'zh-CN', 'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)]);
        $queue->addInsert('snippet_set', ['id' => Uuid::randomBytes(), 'name' => 'BASE en-US', 'base_file' => 'messages.en-US', 'iso' => 'en-US', 'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)]);

        $queue->execute();
    }
    private function createSystemConfigOptions(Connection $connection): void{
        $connection->insert('system_config', [
            'id' => Uuid::randomBytes(),
            'configuration_key' => 'core.store.apiUri',
            'configuration_value' => '{"_value": "https://api.cicada.com"}',
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);

    }
    private function createChannel(Connection $connection): void
    {
        $languages = $connection->executeQuery('SELECT id FROM language')->fetchFirstColumn();
        $rootCategoryId = $connection->executeQuery('SELECT id FROM category')->fetchOne();

        $id = Uuid::fromHexToBytes('98432def39fc4624b33213a56b8c944d');
        $languageEN = Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM);
        $languageDE = Uuid::fromHexToBytes($this->getEnUsLanguageId());

        $connection->insert('channel', [
            'id' => $id,
            'type_id' => Uuid::fromHexToBytes(Defaults::CHANNEL_TYPE_API),
            'access_key' => AccessKeyHelper::generateAccessKey('channel'),
            'active' => 1,
            'language_id' => Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM),
            'navigation_category_id' => $rootCategoryId,
            'navigation_category_version_id' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);

        $connection->insert('channel_translation', ['channel_id' => $id, 'language_id' => $languageEN, 'name' => 'Headless', 'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)]);
        $connection->insert('channel_translation', ['channel_id' => $id, 'language_id' => $languageDE, 'name' => 'Headless', 'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)]);

        // language
        foreach ($languages as $language) {
            $connection->insert('channel_language', ['channel_id' => $id, 'language_id' => $language]);
        }
        
    }
    private function createChannelTypes(Connection $connection): void
    {
        $languageEN = Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM);
        $languageDE = Uuid::fromHexToBytes($this->getEnUsLanguageId());

        $storefront = Uuid::fromHexToBytes(Defaults::CHANNEL_TYPE_WEB);
        $storefrontApi = Uuid::fromHexToBytes(Defaults::CHANNEL_TYPE_API);

        $connection->insert('channel_type', ['id' => $storefront, 'icon_name' => 'default-building-shop', 'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)]);
        $connection->insert('channel_type_translation', ['channel_type_id' => $storefront, 'language_id' => $languageEN, 'name' => 'Web', 'manufacturer' => 'Cicada AG', 'description' => 'Channel with HTML storefront', 'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)]);
        $connection->insert('channel_type_translation', ['channel_type_id' => $storefront, 'language_id' => $languageDE, 'name' => 'Web', 'manufacturer' => 'Cicada AG', 'description' => 'Channel mit HTML storefront', 'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)]);

        $connection->insert('channel_type', ['id' => $storefrontApi, 'icon_name' => 'default-shopping-basket', 'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)]);
        $connection->insert('channel_type_translation', ['channel_type_id' => $storefrontApi, 'language_id' => $languageEN, 'name' => 'Headless', 'manufacturer' => 'Cicada AG', 'description' => 'API only sales channel', 'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)]);
        $connection->insert('channel_type_translation', ['channel_type_id' => $storefrontApi, 'language_id' => $languageDE, 'name' => 'Headless', 'manufacturer' => 'Cicada AG', 'description' => 'API only sales channel', 'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)]);
    }
    private function createRootCategory(Connection $connection): void
    {
        $id = Uuid::randomBytes();
        $languageEN = Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM);
        $languageDE = Uuid::fromHexToBytes($this->getEnUsLanguageId());
        $versionId = Uuid::fromHexToBytes(Defaults::LIVE_VERSION);

        $connection->insert('category', ['id' => $id, 'version_id' => $versionId, 'type' => CategoryDefinition::TYPE_PAGE, 'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)]);
        $connection->insert('category_translation', ['category_id' => $id, 'category_version_id' => $versionId, 'language_id' => $languageEN, 'name' => 'Catalogue #1', 'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)]);
        $connection->insert('category_translation', ['category_id' => $id, 'category_version_id' => $versionId, 'language_id' => $languageDE, 'name' => 'Katalog #1', 'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)]);
    }
    private function createLanguage(Connection $connection): void
    {
        $localeZh = Uuid::randomBytes();
        $localeEn = Uuid::randomBytes();
        $languageZh = Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM);
        $languageEn = Uuid::fromHexToBytes($this->getEnUsLanguageId());

        // first locales
        $connection->insert('locale', ['id' => $localeEn, 'code' => 'en-US', 'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)]);
        $connection->insert('locale', ['id' => $localeZh, 'code' => 'zh-CN', 'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)]);

        // second languages
        $connection->insert('language', [
            'id' => $languageEn,
            'name' => 'English',
            'locale_id' => $localeEn,
            'translation_code_id' => $localeEn,
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);

        $connection->insert('language', [
            'id' => $languageZh,
            'name' => '中文',
            'locale_id' => $localeZh,
            'translation_code_id' => $localeZh,
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);

        // third translations
        $connection->insert('locale_translation', [
            'locale_id' => $localeZh,
            'language_id' => $languageZh,
            'name' => '中文',
            'territory' => '中国',
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);
        $connection->insert('locale_translation', [
            'locale_id' => $localeZh,
            'language_id' => $languageEn,
            'name' => 'Chinese',
            'territory' => 'China',
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);
        $connection->insert('locale_translation', [
            'locale_id' => $localeEn,
            'language_id' => $languageEn,
            'name' => 'Chinese',
            'territory' => 'China',
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);
        $connection->insert('locale_translation', [
            'locale_id' => $localeEn,
            'language_id' => $languageZh,
            'name' => '中文',
            'territory' => '中国',
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);
    }
    private function getEnUsLanguageId(): string
    {
        if (!$this->enUsLanguageId) {
            $this->enUsLanguageId = Uuid::randomHex();
        }

        return $this->enUsLanguageId;
    }
}