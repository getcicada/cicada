<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel\Entity;

use Cicada\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\Exception\ChannelRepositoryNotFoundException;
use Symfony\Component\DependencyInjection\ContainerInterface;

#[Package('frontend')]
class ChannelDefinitionInstanceRegistry extends DefinitionInstanceRegistry
{
    /**
     * @internal
     */
    public function __construct(
        private readonly string $prefix,
        ContainerInterface $container,
        array $definitionMap,
        array $repositoryMap
    ) {
        parent::__construct($container, $definitionMap, $repositoryMap);
    }

    /**
     * @throws ChannelRepositoryNotFoundException
     */
    public function getChannelRepository(string $entityName): ChannelRepository
    {
        $channelRepositoryClass = $this->getChannelRepositoryClassByEntityName($entityName);

        $channelRepository = $this->container->get($channelRepositoryClass);
        \assert($channelRepository instanceof ChannelRepository);

        return $channelRepository;
    }

    public function get(string $class): EntityDefinition
    {
        if (!str_starts_with($class, $this->prefix)) {
            $class = $this->prefix . $class;
        }

        return parent::get($class);
    }

    /**
     * @return array<ChannelDefinitionInterface>
     */
    public function getChannelDefinitions(): array
    {
        return array_filter($this->getDefinitions(), static fn ($definition): bool => $definition instanceof ChannelDefinitionInterface);
    }

    public function register(EntityDefinition $definition, ?string $serviceId = null): void
    {
        if (!$serviceId) {
            $serviceId = $this->prefix . $definition::class;
        }

        parent::register($definition, $serviceId);
    }

    /**
     * @throws ChannelRepositoryNotFoundException
     */
    private function getChannelRepositoryClassByEntityName(string $entityMame): string
    {
        if (!isset($this->repositoryMap[$entityMame])) {
            throw new ChannelRepositoryNotFoundException($entityMame);
        }

        return $this->repositoryMap[$entityMame];
    }
}
