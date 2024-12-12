<?php declare(strict_types=1);

namespace Cicada\Core\Content\Blog\Channel;

use Cicada\Core\Content\Blog\BlogCollection;
use Cicada\Core\Framework\Log\Package;

#[Package('content')]
class ChannelBlogCollection extends BlogCollection
{
    protected function getExpectedClass(): string
    {
        return ChannelBlogEntity::class;
    }
}