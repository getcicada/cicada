<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Test\TestCaseBase;

use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\System\Language\LanguageEntity;
use Symfony\Component\DependencyInjection\ContainerInterface;

trait BasicTestDataBehaviour
{
    public function getEnUsLanguageId(): string
    {
        /** @var EntityRepository $repository */
        $repository = static::getContainer()->get('language.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('language.translationCode.code', 'en-US'));

        /** @var string $languageId */
        $languageId = $repository->searchIds($criteria, Context::createDefaultContext())->firstId();

        return $languageId;
    }

    abstract protected static function getContainer(): ContainerInterface;

    protected function getLocaleIdOfSystemLanguage(): string
    {
        /** @var EntityRepository $repository */
        $repository = static::getContainer()->get('language.repository');

        /** @var LanguageEntity $language */
        $language = $repository->search(new Criteria([Defaults::LANGUAGE_SYSTEM]), Context::createDefaultContext())->get(Defaults::LANGUAGE_SYSTEM);

        return $language->getLocaleId();
    }

    protected function getSnippetSetIdForLocale(string $locale): ?string
    {
        /** @var EntityRepository $repository */
        $repository = static::getContainer()->get('snippet_set.repository');

        $criteria = (new Criteria())
            ->addFilter(new EqualsFilter('iso', $locale))
            ->setLimit(1);

        return $repository->searchIds($criteria, Context::createDefaultContext())->firstId();
    }
}
