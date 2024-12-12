<?php declare(strict_types=1);

namespace Cicada\Core\Content\Blog\Aggregate\BlogTranslation;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCustomFieldsTrait;
use Cicada\Core\Framework\DataAbstractionLayer\TranslationEntity;
use Cicada\Core\Framework\Log\Package;

#[Package('content')]
class BlogTranslationEntity extends TranslationEntity
{
    use EntityCustomFieldsTrait;

}