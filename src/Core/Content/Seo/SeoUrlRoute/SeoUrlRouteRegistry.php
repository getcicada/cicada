<?php declare(strict_types=1);

namespace Cicada\Core\Content\Seo\SeoUrlRoute;

use Cicada\Core\Framework\Log\Package;

#[Package('content')]
class SeoUrlRouteRegistry
{
    /**
     * @var SeoUrlRouteInterface[]
     */
    private array $seoUrlRoutes = [];

    /**
     * @var array<string, list<SeoUrlRouteInterface>>
     */
    private array $definitionToRoutes = [];

    /**
     * @internal
     */
    public function __construct(iterable $seoUrlRoutes)
    {
        /** @var SeoUrlRouteInterface $seoUrlRoute */
        foreach ($seoUrlRoutes as $seoUrlRoute) {
            $config = $seoUrlRoute->getConfig();

            $route = $config->getRouteName();
            $this->seoUrlRoutes[$route] = $seoUrlRoute;
            $entityName = $config->getDefinition()->getEntityName();
            $this->definitionToRoutes[$entityName][] = $seoUrlRoute;
        }
    }

    public function getSeoUrlRoutes(): iterable
    {
        return $this->seoUrlRoutes;
    }

    public function findByRouteName(string $routeName): ?SeoUrlRouteInterface
    {
        return $this->seoUrlRoutes[$routeName] ?? null;
    }

    /**
     * @return SeoUrlRouteInterface[]
     */
    public function findByDefinition(string $definitionName): array
    {
        return $this->definitionToRoutes[$definitionName] ?? [];
    }
}