<?php declare(strict_types=1);

namespace Cicada\Core\Content\Cms\Channel\Struct;

use Cicada\Core\Content\Product\Channel\ChannelProductEntity;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Struct;

#[Package('frontend')]
class ProductBoxStruct extends Struct
{
    /**
     * @var string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $productId;

    /**
     * @var ChannelProductEntity|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $product;

    public function getProduct(): ?ChannelProductEntity
    {
        return $this->product;
    }

    public function setProduct(ChannelProductEntity $product): void
    {
        $this->product = $product;
    }

    public function getProductId(): ?string
    {
        return $this->productId;
    }

    public function setProductId(string $productId): void
    {
        $this->productId = $productId;
    }

    public function getApiAlias(): string
    {
        return 'cms_product_box';
    }
}
