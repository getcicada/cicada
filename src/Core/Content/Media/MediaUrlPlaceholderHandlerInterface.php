<?php declare(strict_types=1);

namespace Cicada\Core\Content\Media;

use Cicada\Core\Framework\Log\Package;

#[Package('frontend')]
interface MediaUrlPlaceholderHandlerInterface
{
    public function replace(string $content): string;
}
