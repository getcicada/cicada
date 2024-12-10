<?php declare(strict_types=1);

namespace Cicada\Core\Content\Cms\Channel;

use Cicada\Core\Content\Cms\CmsPageCollection;
use Cicada\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;
use Symfony\Component\HttpFoundation\Request;

#[Package('frontend')]
interface ChannelCmsPageLoaderInterface
{
    /**
     * @param array<string, mixed>|null $config
     *
     * @return EntitySearchResult<CmsPageCollection>
     */
    public function load(
        Request $request,
        Criteria $criteria,
        ChannelContext $context,
        ?array $config = null,
        ?ResolverContext $resolverContext = null
    ): EntitySearchResult;
}
