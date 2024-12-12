<?php declare(strict_types=1);

namespace Cicada\Core\Content\Blog\Aggregate\BlogMedia;

use Cicada\Core\Content\Blog\BlogDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Content\Media\MediaDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CustomFields;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FkField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\ReverseInherited;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\SetNullOnDelete;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IdField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IntField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\VersionField;
use Cicada\Core\Framework\Feature;

#[Package('content')]
class BlogMediaDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'blog_media';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new ApiAware(), new PrimaryKey(), new Required()),
            (new VersionField())->addFlags(new ApiAware()),

            (new FkField('blog_id', 'blogId', BlogDefinition::class))->addFlags(new ApiAware(), new Required()),
            (new ReferenceVersionField(BlogDefinition::class))->addFlags(new ApiAware(), new Required()),

            (new FkField('media_id', 'mediaId', MediaDefinition::class))->addFlags(new ApiAware(), new Required()),
            (new IntField('position', 'position'))->addFlags(new ApiAware()),
            (new ManyToOneAssociationField('blog', 'blog_id', BlogDefinition::class, 'id'))->addFlags(new ReverseInherited('media')),
            (new ManyToOneAssociationField('media', 'media_id', MediaDefinition::class, 'id', !Feature::isActive('v6.7.0.0')))->addFlags(new ApiAware()),
            (new OneToManyAssociationField('coverProducts', BlogDefinition::class, 'blog_media_id'))->addFlags(new SetNullOnDelete(false)),
            (new CustomFields())->addFlags(new ApiAware()),
        ]);
    }
}