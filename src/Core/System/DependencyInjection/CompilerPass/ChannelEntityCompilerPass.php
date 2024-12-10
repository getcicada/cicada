<?php declare(strict_types=1);

namespace Cicada\Core\System\DependencyInjection\CompilerPass;

use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEventFactory;
use Cicada\Core\Framework\DataAbstractionLayer\Read\EntityReaderInterface;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntityAggregatorInterface;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearcherInterface;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\Entity\ChannelDefinitionInstanceRegistry;
use Cicada\Core\System\Channel\Entity\ChannelRepository;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\Reference;

#[Package('core')]
class ChannelEntityCompilerPass implements CompilerPassInterface
{
    private const PREFIX = 'channel_definition.';

    public function process(ContainerBuilder $container): void
    {
        $this->collectDefinitions($container);
    }

    private function collectDefinitions(ContainerBuilder $container): void
    {
        $entityNameMap = [];
        $repositoryNameMap = [];

        $channelDefinitions = $this->formatData(
            $container->findTaggedServiceIds('cicada.channel.entity.definition'),
            $container
        );

        $baseDefinitions = $this->formatData(
            $container->findTaggedServiceIds('cicada.entity.definition'),
            $container
        );

        $sortedData = $this->sortData($channelDefinitions, $baseDefinitions);

        foreach ($sortedData as $entityName => $definitions) {
            // if extended -> set up
            if (isset($definitions['extended'])) {
                $serviceId = $definitions['extended'];
                $entityNameMap[$entityName] = $serviceId;

                if (isset($definitions['alias'])) {
                    $entityNameMap[$definitions['alias']] = $serviceId;
                }

                $this->setUpEntityDefinitionService($container, $serviceId);
                $container->setAlias(self::PREFIX . $serviceId, new Alias($serviceId, true));
            }

            // if both mask base with extended extended as base
            if (isset($definitions['extended'], $definitions['base'])) {
                $container->setAlias(self::PREFIX . $definitions['base'], new Alias($definitions['extended'], true));
            }

            // if base only clone definition
            if (!isset($definitions['extended']) && isset($definitions['base'])) {
                $service = $container->getDefinition($definitions['base']);

                $clone = clone $service;
                $clone->removeMethodCall('compile');
                $clone->clearTags();
                $container->setDefinition(self::PREFIX . $definitions['base'], $clone);
                $this->setUpEntityDefinitionService($container, self::PREFIX . $definitions['base']);

                $entityNameMap[$entityName] = $definitions['base'];

                if (isset($definitions['alias'])) {
                    $entityNameMap[$definitions['alias']] = $definitions['base'];
                }
            }
        }

        /** @var string $serviceId */
        foreach ($channelDefinitions as $serviceId => $entityNames) {
            $service = $container->getDefinition($serviceId);

            $repositoryId = 'channel.' . $entityNames['entityName'] . '.repository';

            try {
                $repository = $container->getDefinition($repositoryId);
                $repository->setPublic(true);
            } catch (ServiceNotFoundException) {
                $serviceClass = $service->getClass();
                \assert(\is_string($serviceClass));
                $repository = new Definition(
                    ChannelRepository::class,
                    [
                        new Reference($serviceClass),
                        new Reference(EntityReaderInterface::class),
                        new Reference(EntitySearcherInterface::class),
                        new Reference(EntityAggregatorInterface::class),
                        new Reference('event_dispatcher'),
                        new Reference(EntityLoadedEventFactory::class),
                    ]
                );
                $repository->setPublic(true);

                $container->setDefinition($repositoryId, $repository);

                if (isset($entityNames['fallBack'])) {
                    $container->setAlias('channel.' . $entityNames['fallBack'] . '.repository', new Alias($repositoryId, true));
                }
            }

            $repositoryNameMap[$entityNames['entityName']] = $repositoryId;

            if (isset($entityNames['fallBack'])) {
                $repositoryNameMap[$entityNames['fallBack']] = $repositoryId;
            }
        }

        $definitionRegistry = $container->getDefinition(ChannelDefinitionInstanceRegistry::class);
        $definitionRegistry->replaceArgument(0, self::PREFIX);
        $definitionRegistry->replaceArgument(2, $entityNameMap);
        $definitionRegistry->replaceArgument(3, $repositoryNameMap);
    }

    /**
     * @param array<string, array<mixed>> $taggedServiceIds
     *
     * @return array<string, array<string, string>>
     */
    private function formatData(
        array $taggedServiceIds,
        ContainerBuilder $container
    ): array {
        $result = [];

        foreach ($taggedServiceIds as $serviceId => $tags) {
            $service = $container->getDefinition($serviceId);

            /** @var string $class */
            $class = $service->getClass();
            /** @var EntityDefinition $instance */
            $instance = new $class();
            $entityName = $instance->getEntityName();
            $result[$serviceId]['entityName'] = $entityName;

            if (isset($tags[0]['entity'])) {
                $result[$serviceId]['fallBack'] = $tags[0]['entity'];
            }
        }

        return $result;
    }

    /**
     * @param array<string, array<string, string>> $channelDefinitions
     * @param array<string, array<string, string>> $baseDefinitions
     *
     * @return array<string, array<string, string>>
     */
    private function sortData(array $channelDefinitions, array $baseDefinitions): array
    {
        $sorted = [];

        foreach ($baseDefinitions as $serviceId => $entityNames) {
            $sorted[$entityNames['entityName']]['base'] = $serviceId;

            if (isset($entityNames['fallBack'])) {
                $sorted[$entityNames['entityName']]['alias'] = $entityNames['fallBack'];
            }
        }

        foreach ($channelDefinitions as $serviceId => $entityNames) {
            $sorted[$entityNames['entityName']]['extended'] = $serviceId;

            if (isset($entityNames['fallBack'])) {
                $sorted[$entityNames['entityName']]['alias'] = $entityNames['fallBack'];
            }
        }

        return $sorted;
    }

    private function setUpEntityDefinitionService(ContainerBuilder $container, string $serviceId): void
    {
        $service = $container->getDefinition($serviceId);
        $service->setPublic(true);
        $service->addMethodCall('compile', [
            new Reference(ChannelDefinitionInstanceRegistry::class),
        ]);
    }
}
