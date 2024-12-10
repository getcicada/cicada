<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DependencyInjection\CompilerPass;

use League\Flysystem\FilesystemOperator;
use Cicada\Core\Content\Cms\DataResolver\Element\CmsElementResolverInterface;
use Cicada\Core\Content\Seo\SeoUrlRoute\SeoUrlRouteInterface;
use Cicada\Core\Framework\Adapter\Filesystem\Adapter\AdapterFactoryInterface;
use Cicada\Core\Framework\Adapter\Twig\NamespaceHierarchy\TemplateNamespaceHierarchyBuilderInterface;
use Cicada\Core\Framework\DataAbstractionLayer\Dbal\ExceptionHandlerInterface;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\EntityExtension;
use Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\FieldSerializerInterface;
use Cicada\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexer;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;
use Cicada\Core\Framework\Routing\AbstractRouteScope;
use Cicada\Core\Framework\Rule\Rule;
use Cicada\Core\System\NumberRange\ValueGenerator\Pattern\AbstractValueGenerator;
use Cicada\Core\System\Channel\ChannelDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

#[Package('core')]
class AutoconfigureCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $container
            ->registerForAutoconfiguration(EntityDefinition::class)
            ->addTag('cicada.entity.definition');

        $container
            ->registerForAutoconfiguration(ChannelDefinition::class)
            ->addTag('cicada.channel.entity.definition');

        $container
            ->registerForAutoconfiguration(AbstractRouteScope::class)
            ->addTag('cicada.route_scope');

        $container
            ->registerForAutoconfiguration(EntityExtension::class)
            ->addTag('cicada.entity.extension');

        $container
            ->registerForAutoconfiguration(ScheduledTask::class)
            ->addTag('cicada.scheduled.task');

        $container
            ->registerForAutoconfiguration(EntityIndexer::class)
            ->addTag('cicada.entity_indexer');

        $container
            ->registerForAutoconfiguration(ExceptionHandlerInterface::class)
            ->addTag('cicada.dal.exception_handler');

        $container
            ->registerForAutoconfiguration(Rule::class)
            ->addTag('cicada.rule.definition');

        $container
            ->registerForAutoconfiguration(CmsElementResolverInterface::class)
            ->addTag('cicada.cms.data_resolver');

        $container
            ->registerForAutoconfiguration(FieldSerializerInterface::class)
            ->addTag('cicada.field_serializer');

        $container
            ->registerForAutoconfiguration(AdapterFactoryInterface::class)
            ->addTag('cicada.filesystem.factory');

        $container
            ->registerForAutoconfiguration(AbstractValueGenerator::class)
            ->addTag('cicada.value_generator_pattern');
        $container
            ->registerForAutoconfiguration(SeoUrlRouteInterface::class)
            ->addTag('cicada.seo_url.route');

        $container
            ->registerForAutoconfiguration(TemplateNamespaceHierarchyBuilderInterface::class)
            ->addTag('cicada.twig.hierarchy_builder');

        $container->registerAliasForArgument('cicada.filesystem.private', FilesystemOperator::class, 'privateFilesystem');
        $container->registerAliasForArgument('cicada.filesystem.public', FilesystemOperator::class, 'publicFilesystem');
    }
}
