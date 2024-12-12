<?php declare(strict_types=1);

namespace Cicada\Core\Content\Blog\Aggregate\BlogCategory;
use Cicada\Core\Content\Blog\BlogDefinition;
use Cicada\Core\Content\Category\CategoryDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FkField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\DataAbstractionLayer\MappingEntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;

#[Package('content')]
class BlogCategoryDefinition extends MappingEntityDefinition
{
    final public const ENTITY_NAME = 'blog_category';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function isVersionAware(): bool
    {
        return true;
    }
    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new FkField('blog_id', 'blogId', BlogDefinition::class))->addFlags(new PrimaryKey(), new Required()),
            (new ReferenceVersionField(BlogDefinition::class))->addFlags(new PrimaryKey(), new Required()),
            (new FkField('category_id', 'categoryId', CategoryDefinition::class))->addFlags(new PrimaryKey(), new Required()),
            (new ReferenceVersionField(CategoryDefinition::class))->addFlags(new PrimaryKey(), new Required()),
            new ManyToOneAssociationField('blog', 'blog_id', BlogDefinition::class, 'id', false),
            new ManyToOneAssociationField('category', 'category_id', CategoryDefinition::class, 'id', false),
        ]);
    }
}