<?php declare(strict_types=1);

namespace Cicada\Core\Content\Blog;

use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IdField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Log\Package;

#[Package('content')]
class BlogDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'blog';
    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }
    public function getEntityClass(): string
    {
        return BlogEntity::class;
    }
    public function getCollectionClass(): string
    {
        return BlogCollection::class;
    }

    public function getDefaults(): array
    {
        return ['publishedAt' => new \DateTime()];
    }
    protected function defineFields(): FieldCollection{
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey(), new ApiAware()),
            (new BoolField('active', 'active'))->addFlags(new ApiAware()),

        ]);
    }
}