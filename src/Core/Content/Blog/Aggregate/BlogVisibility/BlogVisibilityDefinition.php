<?php declare(strict_types=1);

namespace Cicada\Core\Content\Blog\Aggregate\BlogVisibility;

use Cicada\Core\Content\Blog\BlogDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FkField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IdField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IntField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\System\Channel\ChannelDefinition;

#[Package('content')]
class BlogVisibilityDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'blog_visibility';

    final public const VISIBILITY_LINK = 10;

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return BlogVisibilityEntity::class;
    }

    public function getCollectionClass(): string
    {
        return BlogVisibilityCollection::class;
    }
    protected function getParentDefinitionClass(): ?string
    {
        return BlogDefinition::class;
    }
    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),

            (new FkField('blog_id', 'blogId', BlogDefinition::class))->addFlags(new Required()),
            (new ReferenceVersionField(BlogDefinition::class))->addFlags(new Required()),

            (new FkField('channel_id', 'salesChannelId', ChannelDefinition::class))->addFlags(new Required()),
            (new IntField('visibility', 'visibility'))->addFlags(new Required()),
            new ManyToOneAssociationField('salesChannel', 'channel_id', ChannelDefinition::class, 'id', false),
            new ManyToOneAssociationField('blog', 'blog_id', BlogDefinition::class, 'id', false),
        ]);
    }
}