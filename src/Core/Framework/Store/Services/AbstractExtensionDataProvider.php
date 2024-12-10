<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Store\Services;

use Cicada\Core\Framework\App\AppEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Store\Struct\ExtensionCollection;

/**
 * @internal
 */
#[Package('member')]
abstract class AbstractExtensionDataProvider
{
    abstract public function getInstalledExtensions(Context $context, bool $loadCloudExtensions = true, ?Criteria $searchCriteria = null): ExtensionCollection;
    abstract protected function getDecorated(): AbstractExtensionDataProvider;
}
