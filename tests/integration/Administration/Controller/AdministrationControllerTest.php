<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Administration\Controller;

use Cicada\Core\Framework\Test\TestCaseBase\AdminFunctionalTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class AdministrationControllerTest extends TestCase
{
    use AdminFunctionalTestBehaviour;

    private Connection $connection;

    private string $refreshTokenTtl;

    protected function setup(): void
    {
        $this->connection = static::getContainer()->get(Connection::class);
        $this->refreshTokenTtl = 'P1W';
    }

    public function testSnippetRoute(): void
    {
        $this->getBrowser()->request('GET', '/api/_admin/snippets?locale=en-US');
        static::assertSame(200, $this->getBrowser()->getResponse()->getStatusCode());
        $content = $this->getBrowser()->getResponse()->getContent();
        static::assertNotFalse($content);

        $response = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        static::assertArrayHasKey('en-US', $response);
        static::assertArrayHasKey('zh-CN', $response);
    }

    public function testResetExcludedSearchTermIncorrectLanguageId(): void
    {
        $this->getBrowser()->setServerParameter('HTTP_sw-language-id', Uuid::randomHex());
        $this->getBrowser()->request('POST', '/api/_admin/reset-excluded-search-term');

        $response = $this->getBrowser()->getResponse();

        static::assertSame(412, $response->getStatusCode());
    }
    public function testPreviewSanitizedHtml(): void
    {
        $html = '<img alt="" src="#" /><script type="text/javascript"></script><div>test</div>';
        $browser = $this->createClient();

        $browser->request(
            'POST',
            '/api/_admin/sanitize-html',
            [
                'html' => $html,
                'field' => 'product_translation.description',
            ]
        );

        $content = $this->getBrowser()->getResponse()->getContent();

        static::assertNotFalse($content);

        $response = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        static::assertSame(200, $browser->getResponse()->getStatusCode());
        static::assertSame('<img alt="" src="#" /><div>test</div>', $response['preview']);

        $browser->request(
            'POST',
            '/api/_admin/sanitize-html',
            [
                'html' => $html,
                'field' => 'mail_template_translation.contentHtml',
            ]
        );

        $content = $this->getBrowser()->getResponse()->getContent();

        static::assertNotFalse($content);

        $response = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        static::assertSame(200, $browser->getResponse()->getStatusCode());
        static::assertSame($html, $response['preview']);
    }
}
