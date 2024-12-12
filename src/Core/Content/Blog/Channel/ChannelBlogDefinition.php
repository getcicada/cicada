<?php declare(strict_types=1);

namespace Cicada\Core\Content\Blog\Channel;

use Cicada\Core\Content\Blog\BlogDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;
use Cicada\Core\System\Channel\Entity\ChannelDefinitionInterface;
#[Package('content')]
class ChannelBlogDefinition extends BlogDefinition implements ChannelDefinitionInterface
{
    public function getEntityClass(): string
    {
        return ChannelBlogEntity::class;
    }
    public function getCollectionClass(): string
    {
        return ChannelBlogCollection::class;
    }

    public function processCriteria(Criteria $criteria, ChannelContext $context): void
    {

    }
}