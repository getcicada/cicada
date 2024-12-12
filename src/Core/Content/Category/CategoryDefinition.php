<?php declare(strict_types=1);

namespace Cicada\Core\Content\Category;

use Cicada\Core\Content\Category\Aggregate\CategoryTag\CategoryTagDefinition;
use Cicada\Core\Content\Category\Aggregate\CategoryTranslation\CategoryTranslationDefinition;
use Cicada\Core\Content\Cms\CmsPageDefinition;
use Cicada\Core\Content\Media\MediaDefinition;
use Cicada\Core\Content\Product\Aggregate\ProductCategory\ProductCategoryDefinition;
use Cicada\Core\Content\Product\Aggregate\ProductCategoryTree\ProductCategoryTreeDefinition;
use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Content\ProductStream\ProductStreamDefinition;
use Cicada\Core\Content\Seo\MainCategory\MainCategoryDefinition;
use Cicada\Core\Content\Seo\SeoUrl\SeoUrlDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\AutoIncrementField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ChildCountField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ChildrenAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FkField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\ReverseInherited;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Runtime;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\SearchRanking;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\WriteProtected;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IdField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IntField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ParentAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ParentFkField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\TreeLevelField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\TreePathField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\VersionField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\CustomEntity\CustomEntityDefinition;
use Cicada\Core\System\Channel\ChannelDefinition;
use Cicada\Core\System\Tag\TagDefinition;

#[Package('content')]
class CategoryDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'category';

    final public const TYPE_PAGE = 'page';

    final public const TYPE_LINK = 'link';

    final public const TYPE_FOLDER = 'folder';

    final public const LINK_TYPE_EXTERNAL = 'external';

    final public const LINK_TYPE_CATEGORY = 'category';

    final public const LINK_TYPE_PRODUCT = 'product';

    final public const LINK_TYPE_LANDING_PAGE = 'landing_page';

    final public const PRODUCT_ASSIGNMENT_TYPE_PRODUCT = 'product';

    final public const PRODUCT_ASSIGNMENT_TYPE_PRODUCT_STREAM = 'product_stream';

    final public const CONFIG_KEY_DEFAULT_CMS_PAGE_CATEGORY = 'core.cms.default_category_cms_page';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return CategoryCollection::class;
    }

    public function getEntityClass(): string
    {
        return CategoryEntity::class;
    }

    public function getDefaults(): array
    {
        return [
            'displayNestedProducts' => true,
            'type' => self::TYPE_PAGE,
            'productAssignmentType' => self::PRODUCT_ASSIGNMENT_TYPE_PRODUCT,
        ];
    }

    public function since(): ?string
    {
        return '6.0.0.0';
    }

    public function getHydratorClass(): string
    {
        return CategoryHydrator::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new ApiAware(), new PrimaryKey(), new Required()),
            (new VersionField())->addFlags(new ApiAware()),

            (new ParentFkField(self::class))->addFlags(new ApiAware()),
            (new ReferenceVersionField(self::class, 'parent_version_id'))->addFlags(new ApiAware(), new Required()),

            (new FkField('after_category_id', 'afterCategoryId', self::class))->addFlags(new ApiAware()),
            (new ReferenceVersionField(self::class, 'after_category_version_id'))->addFlags(new ApiAware(), new Required()),

            (new FkField('media_id', 'mediaId', MediaDefinition::class))->addFlags(new ApiAware()),

            (new BoolField('display_nested_products', 'displayNestedProducts'))->addFlags(new ApiAware(), new Required()),
            new AutoIncrementField(),

            (new TranslatedField('breadcrumb'))->addFlags(new ApiAware(), new WriteProtected()),
            (new TreeLevelField('level', 'level'))->addFlags(new ApiAware()),
            (new TreePathField('path', 'path'))->addFlags(new ApiAware()),
            (new ChildCountField())->addFlags(new ApiAware()),

            (new StringField('type', 'type'))->addFlags(new ApiAware(), new Required()),
            (new StringField('product_assignment_type', 'productAssignmentType'))->addFlags(new ApiAware(), new Required()),
            (new BoolField('visible', 'visible'))->addFlags(new ApiAware()),
            (new BoolField('active', 'active'))->addFlags(new ApiAware()),

            (new BoolField('cmsPageIdSwitched', 'cmsPageIdSwitched'))->addFlags(new Runtime(), new ApiAware()),
            (new IntField('visibleChildCount', 'visibleChildCount'))->addFlags(new Runtime(), new ApiAware()),

            (new TranslatedField('name'))->addFlags(new ApiAware(), new SearchRanking(SearchRanking::HIGH_SEARCH_RANKING)),
            (new TranslatedField('customFields'))->addFlags(new ApiAware()),
            new TranslatedField('slotConfig'),
            (new TranslatedField('linkType'))->addFlags(new ApiAware()),
            (new TranslatedField('internalLink'))->addFlags(new ApiAware()),
            (new TranslatedField('externalLink'))->addFlags(new ApiAware()),
            (new TranslatedField('linkNewTab'))->addFlags(new ApiAware()),
            (new TranslatedField('description'))->addFlags(new ApiAware()),
            (new TranslatedField('metaTitle'))->addFlags(new ApiAware()),
            (new TranslatedField('metaDescription'))->addFlags(new ApiAware()),
            (new TranslatedField('keywords'))->addFlags(new ApiAware()),

            (new ParentAssociationField(self::class, 'id'))->addFlags(new ApiAware()),
            (new ChildrenAssociationField(self::class))->addFlags(new ApiAware()),

            (new ManyToOneAssociationField('media', 'media_id', MediaDefinition::class, 'id', false))->addFlags(new ApiAware()),
            (new TranslationsAssociationField(CategoryTranslationDefinition::class, 'category_id'))->addFlags(new ApiAware(), new Required()),
            (new ManyToManyAssociationField('products', ProductDefinition::class, ProductCategoryDefinition::class, 'category_id', 'product_id'))->addFlags(new CascadeDelete(), new ReverseInherited('categories')),
            (new ManyToManyAssociationField('nestedProducts', ProductDefinition::class, ProductCategoryTreeDefinition::class, 'category_id', 'product_id'))->addFlags(new CascadeDelete(), new WriteProtected()),
            (new ManyToManyAssociationField('tags', TagDefinition::class, CategoryTagDefinition::class, 'category_id', 'tag_id'))->addFlags(new ApiAware()),

            (new FkField('cms_page_id', 'cmsPageId', CmsPageDefinition::class))->addFlags(new ApiAware()),
            (new ReferenceVersionField(CmsPageDefinition::class))->addFlags(new Required(), new ApiAware()),
            (new ManyToOneAssociationField('cmsPage', 'cms_page_id', CmsPageDefinition::class, 'id', false))->addFlags(new ApiAware()),
            new FkField('product_stream_id', 'productStreamId', ProductStreamDefinition::class),
            new ManyToOneAssociationField('productStream', 'product_stream_id', ProductStreamDefinition::class, 'id', false),

            // custom entity specific fields
            (new FkField('custom_entity_type_id', 'customEntityTypeId', CustomEntityDefinition::class, 'id'))->addFlags(new ApiAware()),

            // Reverse Associations not available in store-api
            new OneToManyAssociationField('navigationChannels', ChannelDefinition::class, 'navigation_category_id'),
            new OneToManyAssociationField('footerChannels', ChannelDefinition::class, 'footer_category_id'),
            new OneToManyAssociationField('serviceChannels', ChannelDefinition::class, 'service_category_id'),
            (new OneToManyAssociationField('mainCategories', MainCategoryDefinition::class, 'category_id'))->addFlags(new CascadeDelete()),
            (new OneToManyAssociationField('seoUrls', SeoUrlDefinition::class, 'foreign_key'))->addFlags(new ApiAware()),

            (new IntField('visible_child_count', 'visibleChildCount'))->addFlags(new Runtime(), new ApiAware()),
        ]);
    }
}
