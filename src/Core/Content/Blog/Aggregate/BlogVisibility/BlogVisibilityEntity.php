<?php declare(strict_types=1);

namespace Cicada\Core\Content\Blog\Aggregate\BlogVisibility;

use Cicada\Core\Framework\DataAbstractionLayer\Entity;
use Cicada\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Cicada\Core\Framework\Log\Package;

#[Package('content')]
class BlogVisibilityEntity extends Entity
{
    use EntityIdTrait;

}