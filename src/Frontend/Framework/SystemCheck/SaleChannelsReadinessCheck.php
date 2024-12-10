<?php declare(strict_types=1);

namespace Cicada\Frontend\Framework\SystemCheck;

use Doctrine\DBAL\Connection;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\SystemCheck\BaseCheck;
use Cicada\Core\Framework\SystemCheck\Check\Category;
use Cicada\Core\Framework\SystemCheck\Check\Result;
use Cicada\Core\Framework\SystemCheck\Check\Status;
use Cicada\Core\Framework\SystemCheck\Check\SystemCheckExecutionContext;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Kernel;
use Cicada\Core\ChannelRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
#[Package('frontend')]
class SaleChannelsReadinessCheck extends BaseCheck
{
    private const INDEX_PAGE = 'frontend.home.page';

    /**
     * @internal
     */
    public function __construct(
        private readonly Kernel $kernel,
        private readonly RouterInterface $router,
        protected readonly Connection $connection,
        private readonly RequestStack $requestStack
    ) {
    }

    public function run(): Result
    {
        return $this->asAChannelRequest(
            fn () => $this->whileTrustingAllHosts(
                fn () => $this->doRun()
            )
        );
    }

    public function category(): Category
    {
        return Category::FEATURE;
    }

    public function name(): string
    {
        return 'SaleChannelsReadiness';
    }

    protected function allowedSystemCheckExecutionContexts(): array
    {
        return SystemCheckExecutionContext::readiness();
    }

    /**
     * @return array<string>
     */
    protected function fetchChannelDomains(): array
    {
        $result = $this->connection->fetchAllAssociative(
            'SELECT `url` FROM `channel_domain`
                    INNER JOIN `channel` ON `channel_domain`.`channel_id` = `channel`.`id`
                    WHERE `channel`.`type_id` = :typeId
                    AND `channel`.`active` = :active',
            ['typeId' => Uuid::fromHexToBytes(Defaults::CHANNEL_TYPE_STOREFRONT), 'active' => 1]
        );

        return array_map(fn (array $row): string => $row['url'], $result);
    }

    private function doRun(): Result
    {
        $domains = $this->fetchChannelDomains();
        $extra = [];
        $requestStatus = [];
        foreach ($domains as $domain) {
            $url = $this->generateDomainUrl($domain);
            $request = Request::create($url);
            $requestStart = microtime(true);
            $response = $this->kernel->handle($request);
            $responseTime = microtime(true) - $requestStart;
            $status = $response->getStatusCode() >= Response::HTTP_BAD_REQUEST ? Status::FAILURE : Status::OK;
            $requestStatus[$status->name] = $status;

            $extra[] = [
                'storeFrontUrl' => $url,
                'responseCode' => $response->getStatusCode(),
                'responseTime' => $responseTime,
            ];
        }

        $finalStatus = \count($requestStatus) === 1 ? current($requestStatus) : Status::ERROR;

        return new Result(
            $this->name(),
            $finalStatus,
            $finalStatus === Status::OK ? 'All sales channels are OK' : 'Some or all sales channels are unhealthy.',
            $finalStatus === Status::OK,
            $extra
        );
    }

    private function asAChannelRequest(callable $callback): Result
    {
        $mainRequest = $this->requestStack->getMainRequest();
        // the requests originate from CLI, there is no HTTP request.
        if ($mainRequest === null) {
            return $callback();
        }

        // If the request originates from a parent request, regardless of the main request
        // ensure it is treated as a sales channel request to access the frontend
        $hasChannelRequest = $mainRequest->attributes->get(ChannelRequest::ATTRIBUTE_IS_SALES_CHANNEL_REQUEST);
        $mainRequest->attributes->set(ChannelRequest::ATTRIBUTE_IS_SALES_CHANNEL_REQUEST, true);

        try {
            return $callback();
        } finally {
            $mainRequest->attributes->set(ChannelRequest::ATTRIBUTE_IS_SALES_CHANNEL_REQUEST, $hasChannelRequest);
        }
    }

    private function generateDomainUrl(string $url): string
    {
        return rtrim($url, '/') . $this->router->generate(self::INDEX_PAGE);
    }

    private function whileTrustingAllHosts(callable $callback): Result
    {
        $trustedHosts = Request::getTrustedHosts();
        Request::setTrustedHosts([]);
        try {
            return $callback();
        } finally {
            Request::setTrustedHosts($trustedHosts);
        }
    }
}