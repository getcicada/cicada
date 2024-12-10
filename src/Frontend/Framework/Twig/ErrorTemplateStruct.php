<?php declare(strict_types=1);

namespace Cicada\Frontend\Framework\Twig;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Struct;
use Cicada\Frontend\Pagelet\Footer\FooterPagelet;
use Cicada\Frontend\Pagelet\Header\HeaderPagelet;

#[Package('frontend')]
class ErrorTemplateStruct extends Struct
{
    protected ?HeaderPagelet $header;

    protected ?FooterPagelet $footer = null;

    /**
     * @param array<string, \Throwable> $arguments
     */
    public function __construct(
        protected string $templateName = '',
        protected array $arguments = []
    ) {
        $this->header = null;
    }

    public function getTemplateName(): string
    {
        return $this->templateName;
    }

    public function setTemplateName(string $templateName): void
    {
        $this->templateName = $templateName;
    }

    /**
     * @return array<string, \Throwable>
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * @param array<string, \Throwable> $arguments
     */
    public function setArguments(array $arguments): void
    {
        $this->arguments = $arguments;
    }

    public function getHeader(): ?HeaderPagelet
    {
        return $this->header;
    }

    public function setHeader(HeaderPagelet $header): void
    {
        $this->header = $header;
    }

    public function getFooter(): ?FooterPagelet
    {
        return $this->footer;
    }

    public function setFooter(FooterPagelet $footer): void
    {
        $this->footer = $footer;
    }

    public function getApiAlias(): string
    {
        return 'twig_error_template';
    }

    public function isErrorPage(): bool
    {
        return true;
    }
}