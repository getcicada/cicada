<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DependencyInjection;

use Cicada\Core\Framework\Log\Package;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
#[Package('core')]
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('cicada');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                ->append($this->createProfilerSection())
            ->end();

        return $treeBuilder;
    }

    private function createProfilerSection(): ArrayNodeDefinition
    {
        $treeBuilder = new TreeBuilder('profiler');

        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                ->arrayNode('integrations')
                    ->performNoDeepMerging()
                    ->scalarPrototype()
                ->end()
            ->end();

        return $rootNode;
    }
}