<?php declare(strict_types=1);

namespace Cicada\Core\Content\Cms\Channel\Struct;

use Cicada\Core\Content\Product\Channel\CrossSelling\CrossSellingElementCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Struct;

#[Package('frontend')]
class CrossSellingStruct extends Struct
{
    /**
     * @var CrossSellingElementCollection|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $crossSellings;

    public function getCrossSellings(): ?CrossSellingElementCollection
    {
        return $this->crossSellings;
    }

    public function setCrossSellings(CrossSellingElementCollection $crossSellings): void
    {
        $this->crossSellings = $crossSellings;
    }

    public function getApiAlias(): string
    {
        return 'cms_cross_selling';
    }
}