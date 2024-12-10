SET NAMES utf8mb4;
SET
FOREIGN_KEY_CHECKS = 0;

CREATE TABLE `locale`
(
    `id`         binary(16) NOT NULL,
    `code`       varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    `created_at` datetime(3) NOT NULL,
    `updated_at` datetime(3) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq.code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `locale_translation`
(
    `locale_id`     binary(16) NOT NULL,
    `language_id`   binary(16) NOT NULL,
    `name`          varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `territory`     varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `custom_fields` json                                                          DEFAULT NULL,
    `created_at`    datetime(3) NOT NULL,
    `updated_at`    datetime(3) DEFAULT NULL,
    PRIMARY KEY (`locale_id`, `language_id`),
    KEY             `fk.locale_translation.language_id` (`language_id`),
    CONSTRAINT `fk.locale_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk.locale_translation.locale_id` FOREIGN KEY (`locale_id`) REFERENCES `locale` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `json.locale_translation.custom_fields` CHECK (json_valid(`custom_fields`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `language`
(
    `id`                  binary(16) NOT NULL,
    `name`                varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    `parent_id`           binary(16) DEFAULT NULL,
    `locale_id`           binary(16) NOT NULL,
    `translation_code_id` binary(16) DEFAULT NULL,
    `custom_fields`       json DEFAULT NULL,
    `created_at`          datetime(3) NOT NULL,
    `updated_at`          datetime(3) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY                   `idx.language.translation_code_id` (`translation_code_id`),
    KEY                   `idx.language.language_id_parent_language_id` (`id`,`parent_id`),
    KEY                   `fk.language.parent_id` (`parent_id`),
    KEY                   `fk.language.locale_id` (`locale_id`),
    CONSTRAINT `fk.language.locale_id` FOREIGN KEY (`locale_id`) REFERENCES `locale` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk.language.parent_id` FOREIGN KEY (`parent_id`) REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk.language.translation_code_id` FOREIGN KEY (`translation_code_id`) REFERENCES `locale` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `json.language.custom_fields` CHECK (json_valid(`custom_fields`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cms_block`
(
    `id`                     binary(16) NOT NULL,
    `version_id`             binary(16) NOT NULL DEFAULT 0x0FA91CE3E96A4BC2BE4BD9CE752C3425,
    `cms_section_id`         binary(16) DEFAULT NULL,
    `cms_section_version_id` binary(16) NOT NULL DEFAULT 0x0FA91CE3E96A4BC2BE4BD9CE752C3425,
    `position`               int                                     NOT NULL,
    `section_position`       varchar(50) COLLATE utf8mb4_unicode_ci  DEFAULT 'main',
    `type`                   varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    `name`                   varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `locked`                 tinyint(1) NOT NULL DEFAULT '0',
    `margin_top`             varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `margin_bottom`          varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `margin_left`            varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `margin_right`           varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `background_color`       varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `background_media_id`    binary(16) DEFAULT NULL,
    `background_media_mode`  varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `visibility`             json                                    DEFAULT NULL,
    `css_class`              varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `custom_fields`          json                                    DEFAULT NULL,
    `created_at`             datetime(3) NOT NULL,
    `updated_at`             datetime(3) DEFAULT NULL,
    PRIMARY KEY (`id`, `version_id`),
    KEY                      `fk.cms_block.background_media_id` (`background_media_id`),
    KEY                      `fk.cms_block.cms_section_id` (`cms_section_id`,`cms_section_version_id`),
    CONSTRAINT `fk.cms_block.background_media_id` FOREIGN KEY (`background_media_id`) REFERENCES `media` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk.cms_block.cms_section_id` FOREIGN KEY (`cms_section_id`, `cms_section_version_id`) REFERENCES `cms_section` (`id`, `version_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `json.cms_block.custom_fields` CHECK (json_valid(`custom_fields`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cms_page`
(
    `id`               binary(16) NOT NULL,
    `version_id`       binary(16) NOT NULL DEFAULT 0x0FA91CE3E96A4BC2BE4BD9CE752C3425,
    `type`             varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    `entity`           varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `preview_media_id` binary(16) DEFAULT NULL,
    `locked`           tinyint(1) NOT NULL DEFAULT '0',
    `css_class`        varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `config`           json                                    DEFAULT NULL,
    `created_at`       datetime(3) NOT NULL,
    `updated_at`       datetime(3) DEFAULT NULL,
    PRIMARY KEY (`id`, `version_id`),
    KEY                `fk.cms_page.preview_media_id` (`preview_media_id`),
    CONSTRAINT `fk.cms_page.preview_media_id` FOREIGN KEY (`preview_media_id`) REFERENCES `media` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `json.cms_page.config` CHECK (json_valid(`config`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cms_page_translation`
(
    `cms_page_id`         binary(16) NOT NULL,
    `cms_page_version_id` binary(16) NOT NULL DEFAULT 0x0FA91CE3E96A4BC2BE4BD9CE752C3425,
    `language_id`         binary(16) NOT NULL,
    `name`                varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `custom_fields`       json                                    DEFAULT NULL,
    `created_at`          datetime(3) NOT NULL,
    `updated_at`          datetime(3) DEFAULT NULL,
    PRIMARY KEY (`cms_page_id`, `language_id`, `cms_page_version_id`),
    KEY                   `fk.cms_page_translation.language_id` (`language_id`),
    KEY                   `fk.cms_page_translation.cms_page_id` (`cms_page_id`,`cms_page_version_id`),
    CONSTRAINT `fk.cms_page_translation.cms_page_id` FOREIGN KEY (`cms_page_id`, `cms_page_version_id`) REFERENCES `cms_page` (`id`, `version_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk.cms_page_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `json.cms_page_translation.custom_fields` CHECK (json_valid(`custom_fields`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cms_section`
(
    `id`                    binary(16) NOT NULL,
    `version_id`            binary(16) NOT NULL DEFAULT 0x0FA91CE3E96A4BC2BE4BD9CE752C3425,
    `cms_page_id`           binary(16) NOT NULL,
    `cms_page_version_id`   binary(16) NOT NULL DEFAULT 0x0FA91CE3E96A4BC2BE4BD9CE752C3425,
    `position`              int                                     NOT NULL,
    `type`                  varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'default',
    `name`                  varchar(255) COLLATE utf8mb4_unicode_ci          DEFAULT NULL,
    `locked`                tinyint(1) NOT NULL DEFAULT '0',
    `sizing_mode`           varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'boxed',
    `mobile_behavior`       varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'wrap',
    `background_color`      varchar(255) COLLATE utf8mb4_unicode_ci          DEFAULT NULL,
    `background_media_id`   binary(16) DEFAULT NULL,
    `background_media_mode` varchar(255) COLLATE utf8mb4_unicode_ci          DEFAULT NULL,
    `visibility`            json                                             DEFAULT NULL,
    `css_class`             varchar(255) COLLATE utf8mb4_unicode_ci          DEFAULT NULL,
    `custom_fields`         json                                             DEFAULT NULL,
    `created_at`            datetime(3) NOT NULL,
    `updated_at`            datetime(3) DEFAULT NULL,
    PRIMARY KEY (`id`, `version_id`),
    KEY                     `fk.cms_section.background_media_id` (`background_media_id`),
    KEY                     `fk.cms_section.cms_page_id` (`cms_page_id`,`cms_page_version_id`),
    CONSTRAINT `fk.cms_section.background_media_id` FOREIGN KEY (`background_media_id`) REFERENCES `media` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk.cms_section.cms_page_id` FOREIGN KEY (`cms_page_id`, `cms_page_version_id`) REFERENCES `cms_page` (`id`, `version_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `json.cms_section.custom_fields` CHECK (json_valid(`custom_fields`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cms_slot`
(
    `id`                   binary(16) NOT NULL,
    `version_id`           binary(16) NOT NULL,
    `cms_block_id`         binary(16) NOT NULL,
    `cms_block_version_id` binary(16) NOT NULL DEFAULT 0x0FA91CE3E96A4BC2BE4BD9CE752C3425,
    `type`                 varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    `slot`                 varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    `locked`               tinyint(1) NOT NULL DEFAULT '0',
    `created_at`           datetime(3) NOT NULL,
    `updated_at`           datetime(3) DEFAULT NULL,
    PRIMARY KEY (`id`, `version_id`),
    KEY                    `fk.cms_slot.cms_block_id` (`cms_block_id`,`cms_block_version_id`),
    CONSTRAINT `fk.cms_slot.cms_block_id` FOREIGN KEY (`cms_block_id`, `cms_block_version_id`) REFERENCES `cms_block` (`id`, `version_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cms_slot_translation`
(
    `cms_slot_id`         binary(16) NOT NULL,
    `cms_slot_version_id` binary(16) NOT NULL,
    `language_id`         binary(16) NOT NULL,
    `config`              json DEFAULT NULL,
    `custom_fields`       json DEFAULT NULL,
    `created_at`          datetime(3) NOT NULL,
    `updated_at`          datetime(3) DEFAULT NULL,
    PRIMARY KEY (`cms_slot_id`, `cms_slot_version_id`, `language_id`),
    KEY                   `fk.cms_slot_translation.language_id` (`language_id`),
    CONSTRAINT `fk.cms_slot_translation.cms_slot_id` FOREIGN KEY (`cms_slot_id`, `cms_slot_version_id`) REFERENCES `cms_slot` (`id`, `version_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk.cms_slot_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `json.cms_slot_translation.config` CHECK (json_valid(`config`)),
    CONSTRAINT `json.cms_slot_translation.custom_fields` CHECK (json_valid(`custom_fields`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `landing_page`
(
    `id`                  binary(16) NOT NULL,
    `version_id`          binary(16) NOT NULL,
    `active`              tinyint(1) NOT NULL DEFAULT '1',
    `cms_page_id`         binary(16) DEFAULT NULL,
    `cms_page_version_id` binary(16) NOT NULL DEFAULT 0x0FA91CE3E96A4BC2BE4BD9CE752C3425,
    `created_at`          datetime(3) NOT NULL,
    `updated_at`          datetime(3) DEFAULT NULL,
    PRIMARY KEY (`id`, `version_id`),
    KEY                   `fk.landing_page.cms_page_id` (`cms_page_id`,`cms_page_version_id`),
    CONSTRAINT `fk.landing_page.cms_page_id` FOREIGN KEY (`cms_page_id`, `cms_page_version_id`) REFERENCES `cms_page` (`id`, `version_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE `channel_type`
(
    `id`              binary(16) NOT NULL,
    `cover_url`       varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `icon_name`       varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `screenshot_urls` json                                                          DEFAULT NULL,
    `created_at`      datetime(3) NOT NULL,
    `updated_at`      datetime(3) DEFAULT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.channel_type.screenshot_urls` CHECK (json_valid(`screenshot_urls`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `channel`
(
    `id`                       binary(16) NOT NULL,
    `type_id`                  binary(16) NOT NULL,
    `short_name`               varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `configuration`            json                                   DEFAULT NULL,
    `access_key`               varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    `language_id`              binary(16) NOT NULL,
    `active`                   tinyint(1) NOT NULL DEFAULT '1',
    `maintenance`              tinyint(1) NOT NULL DEFAULT '0',
    `maintenance_ip_whitelist` json                                   DEFAULT NULL,
    `created_at`               datetime(3) NOT NULL,
    `updated_at`               datetime(3) DEFAULT NULL,
    `home_cms_page_id`         binary(16) DEFAULT NULL,
    `home_cms_page_version_id` binary(16) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq.access_key` (`access_key`),
    KEY                        `fk.channel.language_id` (`language_id`),
    KEY                        `fk.channel.type_id` (`type_id`),
    KEY                        `fk.channel.home_cms_page` (`home_cms_page_id`,`home_cms_page_version_id`),
    CONSTRAINT `fk.channel.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk.channel.type_id` FOREIGN KEY (`type_id`) REFERENCES `channel_type` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `json.channel.configuration` CHECK (json_valid(`configuration`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `media`
(
    `id`              binary(16) NOT NULL,
    `user_id`         binary(16) DEFAULT NULL,
    `media_folder_id` binary(16) DEFAULT NULL,
    `mime_type`       varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `file_extension`  varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci  DEFAULT NULL,
    `file_size`       int unsigned DEFAULT NULL,
    `meta_data`       json                                                          DEFAULT NULL,
    `file_name`       longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    `media_type`      longblob,
    `thumbnails_ro`   longblob,
    `private`         tinyint(1) NOT NULL DEFAULT '0',
    `uploaded_at`     datetime(3) DEFAULT NULL,
    `created_at`      datetime(3) NOT NULL,
    `updated_at`      datetime(3) DEFAULT NULL,
    `path`            varchar(2048) COLLATE utf8mb4_unicode_ci                      DEFAULT NULL,
    `config`          json                                                          DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY               `fk.media.user_id` (`user_id`),
    KEY               `fk.media.media_folder_id` (`media_folder_id`),
    CONSTRAINT `fk.media.media_folder_id` FOREIGN KEY (`media_folder_id`) REFERENCES `media_folder` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk.media.user_id` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `json.media.meta_data` CHECK (json_valid(`meta_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `media_default_folder`
(
    `id`            binary(16) NOT NULL,
    `entity`        varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    `custom_fields` json DEFAULT NULL,
    `created_at`    datetime(3) NOT NULL,
    `updated_at`    datetime(3) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq.media_default_folder.entity` (`entity`),
    CONSTRAINT `json.media_default_folder.custom_fields` CHECK (json_valid(`custom_fields`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `media_folder`
(
    `id`                            binary(16) NOT NULL,
    `parent_id`                     binary(16) DEFAULT NULL,
    `default_folder_id`             binary(16) DEFAULT NULL,
    `name`                          varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `child_count`                   int unsigned NOT NULL DEFAULT '0',
    `path`                          longtext COLLATE utf8mb4_unicode_ci,
    `media_folder_configuration_id` binary(16) DEFAULT NULL,
    `use_parent_configuration`      tinyint(1) DEFAULT '1',
    `custom_fields`                 json                                                          DEFAULT NULL,
    `created_at`                    datetime(3) NOT NULL,
    `updated_at`                    datetime(3) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq.media_folder.default_folder_id` (`default_folder_id`),
    KEY                             `fk.media_folder.parent_id` (`parent_id`),
    CONSTRAINT `fk.media_folder.default_folder_id` FOREIGN KEY (`default_folder_id`) REFERENCES `media_default_folder` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk.media_folder.parent_id` FOREIGN KEY (`parent_id`) REFERENCES `media_folder` (`id`) ON DELETE CASCADE,
    CONSTRAINT `json.media_folder.custom_fields` CHECK (json_valid(`custom_fields`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `media_folder_configuration`
(
    `id`                       binary(16) NOT NULL,
    `create_thumbnails`        tinyint(1) DEFAULT '1',
    `thumbnail_quality`        int  DEFAULT '80',
    `media_thumbnail_sizes_ro` longblob,
    `keep_aspect_ratio`        tinyint(1) DEFAULT '1',
    `private`                  tinyint(1) DEFAULT '0',
    `no_association`           tinyint(1) DEFAULT NULL,
    `custom_fields`            json DEFAULT NULL,
    `created_at`               datetime(3) NOT NULL,
    `updated_at`               datetime(3) DEFAULT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.media_folder_configuration.custom_fields` CHECK (json_valid(`custom_fields`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `media_folder_configuration_media_thumbnail_size`
(
    `media_folder_configuration_id` binary(16) NOT NULL,
    `media_thumbnail_size_id`       binary(16) NOT NULL,
    PRIMARY KEY (`media_folder_configuration_id`, `media_thumbnail_size_id`),
    KEY                             `fk.media_folder_configuration_media_thumbnail_size.size_id` (`media_thumbnail_size_id`),
    CONSTRAINT `fk.media_folder_configuration_media_thumbnail_size.conf_id` FOREIGN KEY (`media_folder_configuration_id`) REFERENCES `media_folder_configuration` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk.media_folder_configuration_media_thumbnail_size.size_id` FOREIGN KEY (`media_thumbnail_size_id`) REFERENCES `media_thumbnail_size` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `media_thumbnail`
(
    `id`            binary(16) NOT NULL,
    `media_id`      binary(16) NOT NULL,
    `width`         int unsigned NOT NULL,
    `height`        int unsigned NOT NULL,
    `custom_fields` json                                     DEFAULT NULL,
    `created_at`    datetime(3) NOT NULL,
    `updated_at`    datetime(3) DEFAULT NULL,
    `path`          varchar(2048) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY             `fk.media_thumbnail.media_id` (`media_id`),
    CONSTRAINT `fk.media_thumbnail.media_id` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `json.media_thumbnail.custom_fields` CHECK (json_valid(`custom_fields`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `media_thumbnail_size`
(
    `id`            binary(16) NOT NULL,
    `width`         int NOT NULL,
    `height`        int NOT NULL,
    `custom_fields` json DEFAULT NULL,
    `created_at`    datetime(3) NOT NULL,
    `updated_at`    datetime(3) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq.width` (`width`,`height`),
    CONSTRAINT `json.media_thumbnail_size.custom_fields` CHECK (json_valid(`custom_fields`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `media_translation`
(
    `media_id`      binary(16) NOT NULL,
    `language_id`   binary(16) NOT NULL,
    `alt`           varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `title`         varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `custom_fields` json                                                          DEFAULT NULL,
    `created_at`    datetime(3) NOT NULL,
    `updated_at`    datetime(3) DEFAULT NULL,
    PRIMARY KEY (`media_id`, `language_id`),
    KEY             `fk.media_translation.language_id` (`language_id`),
    CONSTRAINT `fk.media_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk.media_translation.media_id` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `json.media_translation.custom_fields` CHECK (json_valid(`custom_fields`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `channel_domain`
(
    `id`                       binary(16) NOT NULL,
    `channel_id`         binary(16) NOT NULL,
    `language_id`              binary(16) NOT NULL,
    `url`                      varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    `snippet_set_id`           binary(16) NOT NULL,
    `hreflang_use_only_locale` tinyint unsigned DEFAULT '0',
    `custom_fields`            json DEFAULT NULL,
    `created_at`               datetime(3) NOT NULL,
    `updated_at`               datetime(3) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq.channel_domain.url` (`url`),
    KEY                        `fk.channel_domain.snippet_set_id` (`snippet_set_id`),
    KEY                        `fk.channel_domain.language_id` (`language_id`),
    KEY                        `fk.channel_domain.channel_id` (`channel_id`),
    CONSTRAINT `fk.channel_domain.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk.channel_domain.channel_id` FOREIGN KEY (`channel_id`) REFERENCES `channel` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk.channel_domain.snippet_set_id` FOREIGN KEY (`snippet_set_id`) REFERENCES `snippet_set` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `json.channel_domain.custom_fields` CHECK (json_valid(`custom_fields`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET
FOREIGN_KEY_CHECKS = 1;