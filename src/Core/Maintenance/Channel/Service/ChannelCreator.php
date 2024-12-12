<?php declare(strict_types=1);

namespace Cicada\Core\Maintenance\Channel\Service;

use Cicada\Core\Defaults;
use Cicada\Core\Framework\Api\Util\AccessKeyHelper;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Maintenance\MaintenanceException;

/**
 * @internal
 */
#[Package('core')]
class ChannelCreator
{
    /**
     * @internal
     */
    public function __construct(
        private readonly DefinitionInstanceRegistry $definitionRegistry,
        private readonly EntityRepository $channelRepository,
        private readonly EntityRepository $categoryRepository

    ) {
    }

    /**
     * @param list<string>|null $languages
     * @param array<string, mixed> $overwrites
     */
    public function createChannel(
        string $id,
        string $name,
        string $typeId,
        ?string $languageId = null,
        ?string $navigationCategoryId = null,
        ?array $languages = null,
        array $overwrites = []
    ): string {
        $context = Context::createDefaultContext();
        $languageId ??= Defaults::LANGUAGE_SYSTEM;
        $languages = $this->formatToMany($languages, $languageId, 'language', $context);
        $data = [
            'id' => $id,
            'name' => $name,
            'typeId' => $typeId,
            'accessKey' => AccessKeyHelper::generateAccessKey('channel'),
            'navigationCategoryId' => $navigationCategoryId ?? $this->getRootCategoryId($context),

            // default selection
            'languageId' => $languageId,
            // available mappings
            'languages' => $languages,
        ];

        $data = array_replace_recursive($data, $overwrites);

        $this->channelRepository->create([$data], $context);

        return $data['accessKey'];
    }
    private function getRootCategoryId(Context $context): string
    {
        $criteria = new Criteria();
        $criteria->setLimit(1);
        $criteria->addFilter(new EqualsFilter('category.parentId', null));
        $criteria->addSorting(new FieldSorting('category.createdAt', FieldSorting::ASCENDING));

        $categoryId = $this->categoryRepository->searchIds($criteria, $context)->firstId();
        if (!\is_string($categoryId)) {
            throw MaintenanceException::couldNotGetId('root category');
        }

        return $categoryId;
    }
    /**
     * @return array<array{id: string}>
     */
    private function getAllIdsOf(string $entity, Context $context): array
    {
        /** @var array<string> $ids */
        $ids = $this->definitionRegistry->getRepository($entity)->searchIds(new Criteria(), $context)->getIds();

        return array_map(
            static fn (string $id): array => ['id' => $id],
            $ids
        );
    }

    /**
     * @param list<string>|null $values
     *
     * @return array<array{id: string}>
     */
    private function formatToMany(?array $values, string $default, string $entity, Context $context): array
    {
        if (!\is_array($values)) {
            return $this->getAllIdsOf($entity, $context);
        }

        $values = array_unique(array_merge($values, [$default]));

        return array_map(
            static fn (string $id): array => ['id' => $id],
            $values
        );
    }
}
