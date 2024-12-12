<?php declare(strict_types=1);

namespace Cicada\Core\Content\Blog;
use Cicada\Core\Content\Blog\Events\BlogIndexerEvent;
use Cicada\Core\Framework\Log\Package;

#[Package('content')]
class BlogEvents
{
    final public const BLOG_INDEXER_EVENT = BlogIndexerEvent::class;

}