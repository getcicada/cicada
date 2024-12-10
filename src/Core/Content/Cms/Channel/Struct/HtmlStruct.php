<?php declare(strict_types=1);

namespace Cicada\Core\Content\Cms\Channel\Struct;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Struct;

#[Package('frontend')]
class HtmlStruct extends Struct
{
    protected ?string $content = null;

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function getApiAlias(): string
    {
        return 'cms_html';
    }
}
