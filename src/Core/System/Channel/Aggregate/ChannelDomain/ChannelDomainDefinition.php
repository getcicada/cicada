<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel\Aggregate\ChannelDomain;

use Cicada\Core\Content\ProductExport\ProductExportDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CustomFields;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FkField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IdField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Currency\CurrencyDefinition;
use Cicada\Core\System\Language\LanguageDefinition;
use Cicada\Core\System\Channel\ChannelDefinition;
use Cicada\Core\System\Snippet\Aggregate\SnippetSet\SnippetSetDefinition;

#[Package('frontend')]
class ChannelDomainDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'channel_domain';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return ChannelDomainEntity::class;
    }

    public function getCollectionClass(): string
    {
        return ChannelDomainCollection::class;
    }

    public function since(): ?string
    {
        return '6.0.0.0';
    }

    protected function getParentDefinitionClass(): ?string
    {
        return ChannelDefinition::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new ApiAware(), new PrimaryKey(), new Required()),

            (new StringField('url', 'url', 255))->addFlags(new ApiAware(), new Required()),
            (new FkField('channel_id', 'channelId', ChannelDefinition::class))->addFlags(new ApiAware(), new Required()),
            (new FkField('language_id', 'languageId', LanguageDefinition::class))->addFlags(new ApiAware(), new Required()),
            (new FkField('currency_id', 'currencyId', CurrencyDefinition::class))->addFlags(new ApiAware(), new Required()),
            (new FkField('snippet_set_id', 'snippetSetId', SnippetSetDefinition::class))->addFlags(new ApiAware(), new Required()),
            new ManyToOneAssociationField('channel', 'channel_id', ChannelDefinition::class, 'id', false),
            (new ManyToOneAssociationField('language', 'language_id', LanguageDefinition::class, 'id', false))->addFlags(new ApiAware()),
            (new ManyToOneAssociationField('currency', 'currency_id', CurrencyDefinition::class, 'id', false))->addFlags(new ApiAware()),
            new ManyToOneAssociationField('snippetSet', 'snippet_set_id', SnippetSetDefinition::class, 'id', false),
            (new OneToOneAssociationField('channelDefaultHreflang', 'id', 'hreflang_default_domain_id', ChannelDefinition::class, false))->addFlags(new ApiAware()),
            new OneToManyAssociationField('productExports', ProductExportDefinition::class, 'channel_domain_id', 'id'),
            (new BoolField('hreflang_use_only_locale', 'hreflangUseOnlyLocale'))->addFlags(new ApiAware()),
            (new CustomFields())->addFlags(new ApiAware()),
        ]);
    }
}
