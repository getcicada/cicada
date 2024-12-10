<?php declare(strict_types=1);

namespace Cicada\Frontend\Page;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Struct;
use Cicada\Frontend\Pagelet\Footer\FooterPagelet;
use Cicada\Frontend\Pagelet\Header\HeaderPagelet;

#[Package('frontend')]
class Page extends Struct
{

    protected ?HeaderPagelet $header;

    protected ?FooterPagelet $footer = null;
    protected MetaInformation $metaInformation;

    public function getHeader(): ?HeaderPagelet
    {
        return $this->header;
    }

    public function setHeader(?HeaderPagelet $header): void
    {
        $this->header = $header;
    }

    public function getFooter(): ?FooterPagelet
    {
        return $this->footer;
    }

    public function setFooter(?FooterPagelet $footer): void
    {
        $this->footer = $footer;
    }
    public function getMetaInformation(): ?MetaInformation
    {
        return $this->metaInformation;
    }

    public function setMetaInformation(MetaInformation $metaInformation): void
    {
        $this->metaInformation = $metaInformation;
    }
}
