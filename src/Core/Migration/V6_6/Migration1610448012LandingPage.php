<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_6;

use Doctrine\DBAL\Connection;
use CIcada\Core\Framework\Log\Package;
use CIcada\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
class Migration1610448012LandingPage extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1610448012;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
             CREATE TABLE `landing_page` (
                  `id` binary(16) NOT NULL,
                  `version_id` binary(16) NOT NULL,
                  `active` tinyint(1) NOT NULL DEFAULT "1",
                  `cms_page_id` binary(16) DEFAULT NULL,
                  `created_at` datetime(3) NOT NULL,
                  `updated_at` datetime(3) DEFAULT NULL,
                  PRIMARY KEY (`id`,`version_id`),
                  KEY `fk.landing_page.cms_page_id` (`cms_page_id`),
                  CONSTRAINT `fk.landing_page.cms_page_id` FOREIGN KEY (`cms_page_id`) REFERENCES `cms_page` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

        $connection->executeStatement('
            CREATE TABLE `landing_page_translation`
            (
                `landing_page_id`         binary(16) NOT NULL,
                `landing_page_version_id` binary(16) NOT NULL,
                `language_id`             binary(16) NOT NULL,
                `name`                    varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                `url`                     varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                `meta_title`              varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                `meta_description`        varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                `keywords`                varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                `custom_fields`           json                                    DEFAULT NULL,
                `slot_config`             json                                    DEFAULT NULL,
                `created_at`              datetime(3) NOT NULL,
                `updated_at`              datetime(3) DEFAULT NULL,
                PRIMARY KEY (`landing_page_id`, `landing_page_version_id`, `language_id`),
                KEY                       `fk.landing_page_translation.language_id` (`language_id`),
                CONSTRAINT `fk.landing_page_translation.landing_page_id` FOREIGN KEY (`landing_page_id`, `landing_page_version_id`) REFERENCES `landing_page` (`id`, `version_id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.landing_page_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `json.landing_page_translation.custom_fields` CHECK (json_valid(`custom_fields`)),
                CONSTRAINT `json.landing_page_translation.slot_config` CHECK (json_valid(`slot_config`))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

        $connection->executeStatement('
            CREATE TABLE `landing_page_tag` (
              `landing_page_id` binary(16) NOT NULL,
              `landing_page_version_id` binary(16) NOT NULL,
              `tag_id` binary(16) NOT NULL,
              PRIMARY KEY (`landing_page_id`,`landing_page_version_id`,`tag_id`),
              KEY `fk.landing_page_tag.tag_id` (`tag_id`),
              CONSTRAINT `fk.landing_page_tag.landing_page_version_id__landing_page_id` FOREIGN KEY (`landing_page_id`, `landing_page_version_id`) REFERENCES `landing_page` (`id`, `version_id`) ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `fk.landing_page_tag.tag_id` FOREIGN KEY (`tag_id`) REFERENCES `tag` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

        $connection->executeStatement('
            CREATE TABLE `landing_page_channel` (
              `landing_page_id` binary(16) NOT NULL,
              `landing_page_version_id` binary(16) NOT NULL,
              `channel_id` binary(16) NOT NULL,
              PRIMARY KEY (`landing_page_id`,`landing_page_version_id`,`channel_id`),
              KEY `fk.landing_page_channel.channel_id` (`channel_id`),
              CONSTRAINT `fk.landing_page_channel.product_id` FOREIGN KEY (`landing_page_id`, `landing_page_version_id`) REFERENCES `landing_page` (`id`, `version_id`) ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `fk.landing_page_channel.channel_id` FOREIGN KEY (`channel_id`) REFERENCES `channel` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
