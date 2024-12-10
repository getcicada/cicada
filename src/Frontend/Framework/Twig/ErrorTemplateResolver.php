<?php declare(strict_types=1);

namespace Cicada\Frontend\Framework\Twig;

use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Twig\Environment;

#[Package('frontend')]
class ErrorTemplateResolver
{
    /**
     * @var Environment
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $twig;

    /**
     * @internal
     */
    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function resolve(\Throwable $exception, Request $request): ErrorTemplateStruct
    {
        $template = '@Frontend/frontend/page/error/error';

        if ($request->isXmlHttpRequest()) {
            $template .= '-ajax';
        }

        $code = $exception->getCode();

        if ($exception instanceof HttpException) {
            $code = $exception->getStatusCode();
        }

        $dedicatedTemplate = $template . '-' . $code;

        if ($this->twig->getLoader()->exists($dedicatedTemplate . '.html.twig')) {
            $template = $dedicatedTemplate;
        } else {
            $template .= '-std';
        }

        $template .= '.html.twig';

        return new ErrorTemplateStruct($template, ['exception' => $exception]);
    }
}