<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Frontend\Page\LandingPage;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\LandingPage\LandingPageDefinition;
use Cicada\Core\Content\LandingPage\LandingPageEntity;
use Cicada\Core\Framework\Log\Package;
use Cicada\Frontend\Page\LandingPage\LandingPage;

/**
 * @internal
 */
#[Package('frontend')]
#[CoversClass(LandingPage::class)]
class LandingPageTest extends TestCase
{
    public function testLandingPage(): void
    {
        $page = new LandingPage();
        $entity = new LandingPageEntity();

        $page->setLandingPage($entity);

        static::assertSame(LandingPageDefinition::ENTITY_NAME, $page->getEntityName());
        static::assertSame($entity, $page->getLandingPage());
    }
}
