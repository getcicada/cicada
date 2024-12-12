<?php declare(strict_types=1);

namespace Cicada\Core\Content\Category\Service;

use Cicada\Core\Content\Category\CategoryEntity;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelEntity;

#[Package('content')]
abstract class AbstractCategoryUrlGenerator
{
    abstract public function getDecorated(): AbstractCategoryUrlGenerator;

    abstract public function generate(CategoryEntity $category, ?ChannelEntity $channel): ?string;
}
