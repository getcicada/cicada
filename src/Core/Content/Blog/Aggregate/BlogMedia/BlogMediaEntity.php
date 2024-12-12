<?php declare(strict_types=1);

namespace Cicada\Core\Content\Blog\Aggregate\BlogMedia;

use Cicada\Core\Framework\DataAbstractionLayer\Attribute\Entity;
use Cicada\Core\Framework\DataAbstractionLayer\EntityCustomFieldsTrait;
use Cicada\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Cicada\Core\Framework\Log\Package;

#[Package('content')]
class BlogMediaEntity extends Entity
{
    use EntityCustomFieldsTrait;
    use EntityIdTrait;
}