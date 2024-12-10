<?php declare(strict_types=1);

namespace Cicada\Frontend\Event;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\NestedEvent;
use Cicada\Core\Framework\Event\CicadaChannelEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;
use Symfony\Component\HttpFoundation\Request;

#[Package('frontend')]
class FrontendRenderEvent extends NestedEvent implements CicadaChannelEvent
{
    /**
     * @var string
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $view;

    /**
     * @var array<string, mixed>
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $parameters;

    /**
     * @var Request
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $request;

    /**
     * @var ChannelContext
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $context;

    /**
     * @param array<string, mixed> $parameters
     */
    public function __construct(
        string $view,
        array $parameters,
        Request $request,
        ChannelContext $context
    ) {
        $this->view = $view;
        $this->parameters = array_merge(['context' => $context], $parameters);
        $this->request = $request;
        $this->context = $context;
    }

    public function getChannelContext(): ChannelContext
    {
        return $this->context;
    }

    public function setChannelContext(ChannelContext $context): void
    {
        $this->context = $context;
    }

    public function getContext(): Context
    {
        return $this->context->getContext();
    }

    public function getView(): string
    {
        return $this->view;
    }

    /**
     * @return array<string, mixed>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * @param mixed $value
     */
    public function setParameter(string $key, $value): void
    {
        $this->parameters[$key] = $value;
    }
}
