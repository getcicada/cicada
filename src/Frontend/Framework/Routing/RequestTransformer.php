<?php declare(strict_types=1);

namespace Cicada\Frontend\Framework\Routing;

use Cicada\Core\Content\Seo\AbstractSeoResolver;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Routing\RequestTransformerInterface;
use Cicada\Core\PlatformRequest;
use Cicada\Core\ChannelRequest;
use Cicada\Frontend\Framework\Routing\Exception\ChannelMappingException;
use Symfony\Component\HttpFoundation\Request;

/**
 * @phpstan-import-type Domain from AbstractDomainLoader
 * @phpstan-import-type ResolvedSeoUrl from AbstractSeoResolver
 */
#[Package('frontend')]
class RequestTransformer implements RequestTransformerInterface
{
    /**
     * Virtual path of the "domain"
     *
     * @example
     * - `/de`
     * - `/en`
     * - {empty} - the virtual path is optional
     */
    final public const SALES_CHANNEL_BASE_URL = 'sw-sales-channel-base-url';

    /**
     * Scheme + Host + port + subdir in web root
     *
     * @example
     * - `https://shop.example` - no subdir
     * - `http://localhost:8000/subdir` - with sub dir `/subdir`
     */
    final public const SALES_CHANNEL_ABSOLUTE_BASE_URL = 'sw-sales-channel-absolute-base-url';

    /**
     * Scheme + Host + port + subdir in web root + virtual path
     *
     * @example
     * - `https://shop.example` - no sub dir and no virtual path
     * - `https://shop.example/en` - no sub dir and virtual path `/en`
     * - `http://localhost:8000/subdir` - with sub directory `/subdir`
     * - `http://localhost:8000/subdir/de` - with sub directory `/subdir` and virtual path `/de`
     */
    final public const FRONTEND_URL = 'sw-frontend-url';

    final public const SALES_CHANNEL_RESOLVED_URI = 'resolved-uri';

    final public const ORIGINAL_REQUEST_URI = 'sw-original-request-uri';

    private const INHERITABLE_ATTRIBUTE_NAMES = [
        self::SALES_CHANNEL_BASE_URL,
        self::SALES_CHANNEL_ABSOLUTE_BASE_URL,
        self::FRONTEND_URL,
        self::SALES_CHANNEL_RESOLVED_URI,

        PlatformRequest::ATTRIBUTE_CHANNEL_ID,
        ChannelRequest::ATTRIBUTE_IS_SALES_CHANNEL_REQUEST,

        ChannelRequest::ATTRIBUTE_DOMAIN_LOCALE,
        ChannelRequest::ATTRIBUTE_DOMAIN_SNIPPET_SET_ID,
        ChannelRequest::ATTRIBUTE_DOMAIN_ID,

        ChannelRequest::ATTRIBUTE_THEME_ID,
        ChannelRequest::ATTRIBUTE_THEME_NAME,
        ChannelRequest::ATTRIBUTE_THEME_BASE_NAME,

        ChannelRequest::ATTRIBUTE_CANONICAL_LINK,
    ];

    /**
     * @var array<string>
     */
    private array $allowedList = [
        '/_wdt/',
        '/_profiler/',
        '/_error/',
        '/payment/finalize-transaction',
        '/installer',
        '/_fragment/',
    ];

    /**
     * @internal
     *
     * @param array<string> $registeredApiPrefixes
     */
    public function __construct(
        private readonly RequestTransformerInterface $decorated,
        private readonly AbstractSeoResolver $resolver,
        private readonly array $registeredApiPrefixes,
        private readonly AbstractDomainLoader $domainLoader
    ) {
    }

    public function transform(Request $request): Request
    {
        $request = $this->decorated->transform($request);

        if (!$this->isChannelRequired($request->getPathInfo())) {
            return $this->decorated->transform($request);
        }

        $channel = $this->findChannel($request);
        if ($channel === null) {
            // this class and therefore the "isChannelRequired" method is currently not extendable
            // which can cause problems when adding custom paths
            throw new ChannelMappingException($request->getUri());
        }

        $absoluteBaseUrl = $this->getSchemeAndHttpHost($request) . $request->getBaseUrl();
        $baseUrl = str_replace($absoluteBaseUrl, '', $channel['url']);

        $resolved = $this->resolveSeoUrl(
            $request,
            $baseUrl,
            $channel['languageId'],
            $channel['channelId']
        );

        $currentRequestUri = $request->getRequestUri();

        /**
         * - Remove "virtual" suffix of domain mapping cicada.de/de
         * - To get only the host cicada.de as real request uri cicada.de/
         * - Resolve remaining seo url and get the real path info cicada.de/outdoor => cicada.de/navigation/{id}
         *
         * Possible domains
         *
         * same host, different "virtual" suffix
         * http://cicada.de/de
         * http://cicada.de/en
         * http://cicada.de/fr
         *
         * same host, different location
         * http://cicada.fr
         * http://cicada.com
         * http://cicada.de
         *
         * complete different host and location
         * http://color.com
         * http://farben.de
         * http://couleurs.fr
         *
         * installation in sub directory
         * http://localhost/development/public/de
         * http://localhost/development/public/en
         * http://localhost/development/public/fr
         *
         * installation with port
         * http://localhost:8080
         * http://localhost:8080/en
         * http://localhost:8080/fr
         */
        $transformedServerVars = array_merge(
            $request->server->all(),
            ['REQUEST_URI' => rtrim($request->getBaseUrl(), '/') . $resolved['pathInfo']]
        );

        $transformedRequest = $request->duplicate(null, null, null, null, null, $transformedServerVars);
        $transformedRequest->attributes->set(self::SALES_CHANNEL_BASE_URL, $baseUrl);
        $transformedRequest->attributes->set(self::SALES_CHANNEL_ABSOLUTE_BASE_URL, rtrim($absoluteBaseUrl, '/'));
        $transformedRequest->attributes->set(
            self::FRONTEND_URL,
            $transformedRequest->attributes->get(self::SALES_CHANNEL_ABSOLUTE_BASE_URL)
            . $transformedRequest->attributes->get(self::SALES_CHANNEL_BASE_URL)
        );
        $transformedRequest->attributes->set(self::SALES_CHANNEL_RESOLVED_URI, $resolved['pathInfo']);

        $transformedRequest->attributes->set(PlatformRequest::ATTRIBUTE_CHANNEL_ID, $channel['channelId']);
        $transformedRequest->attributes->set(ChannelRequest::ATTRIBUTE_IS_SALES_CHANNEL_REQUEST, true);
        $transformedRequest->attributes->set(ChannelRequest::ATTRIBUTE_DOMAIN_LOCALE, $channel['locale']);
        $transformedRequest->attributes->set(ChannelRequest::ATTRIBUTE_DOMAIN_SNIPPET_SET_ID, $channel['snippetSetId']);
        $transformedRequest->attributes->set(ChannelRequest::ATTRIBUTE_DOMAIN_ID, $channel['id']);
        $transformedRequest->attributes->set(ChannelRequest::ATTRIBUTE_THEME_ID, $channel['themeId']);
        $transformedRequest->attributes->set(ChannelRequest::ATTRIBUTE_THEME_NAME, $channel['themeName']);
        $transformedRequest->attributes->set(ChannelRequest::ATTRIBUTE_THEME_BASE_NAME, $channel['parentThemeName']);

        $transformedRequest->attributes->set(
            ChannelRequest::ATTRIBUTE_SALES_CHANNEL_MAINTENANCE,
            (bool) $channel['maintenance']
        );

        $transformedRequest->attributes->set(
            ChannelRequest::ATTRIBUTE_SALES_CHANNEL_MAINTENANCE_IP_WHITLELIST,
            $channel['maintenanceIpWhitelist']
        );

        if (isset($resolved['canonicalPathInfo'])) {
            $urlPath = parse_url($channel['url'], \PHP_URL_PATH);
            if ($urlPath === false || $urlPath === null) {
                $urlPath = '';
            }

            $baseUrlPath = trim($urlPath, '/');
            if (\strlen($baseUrlPath) > 1 && !str_starts_with($baseUrlPath, '/')) {
                $baseUrlPath = '/' . $baseUrlPath;
            }

            $transformedRequest->attributes->set(
                ChannelRequest::ATTRIBUTE_CANONICAL_LINK,
                $this->getSchemeAndHttpHost($request) . $baseUrlPath . $resolved['canonicalPathInfo']
            );
        }

        $transformedRequest->headers->add($request->headers->all());
        $transformedRequest->headers->set(PlatformRequest::HEADER_LANGUAGE_ID, $channel['languageId']);
        $transformedRequest->attributes->set(self::ORIGINAL_REQUEST_URI, $currentRequestUri);

        return $transformedRequest;
    }

    /**
     * @return array<string, mixed>
     */
    public function extractInheritableAttributes(Request $sourceRequest): array
    {
        $inheritableAttributes = $this->decorated
            ->extractInheritableAttributes($sourceRequest);

        foreach (self::INHERITABLE_ATTRIBUTE_NAMES as $attributeName) {
            if (!$sourceRequest->attributes->has($attributeName)) {
                continue;
            }

            $inheritableAttributes[$attributeName] = $sourceRequest->attributes->get($attributeName);
        }

        return $inheritableAttributes;
    }

    private function isChannelRequired(string $pathInfo): bool
    {
        $pathInfo = '/' . trim($pathInfo, '/') . '/';

        foreach ($this->registeredApiPrefixes as $apiPrefix) {
            if (str_starts_with($pathInfo, '/' . $apiPrefix . '/')) {
                return false;
            }
        }

        foreach ($this->allowedList as $prefix) {
            if (str_starts_with($pathInfo, $prefix)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return Domain|null
     */
    private function findChannel(Request $request): ?array
    {
        $domains = $this->domainLoader->load();

        if (empty($domains)) {
            return null;
        }

        // domain urls and request uri should be in same format, all with trailing slash
        $requestUrl = rtrim($this->getSchemeAndHttpHost($request) . $request->getBasePath() . $request->getPathInfo(), '/') . '/';

        // direct hit
        if (\array_key_exists($requestUrl, $domains)) {
            $domain = $domains[$requestUrl];
            $domain['url'] = rtrim($domain['url'], '/');

            return $domain;
        }

        // reduce shops to which base url is the beginning of the request
        $domains = array_filter($domains, fn ($baseUrl): bool => str_starts_with($requestUrl, $baseUrl), \ARRAY_FILTER_USE_KEY);

        if (empty($domains)) {
            return null;
        }

        // determine most matching shop base url
        $lastBaseUrl = '';
        $bestMatch = current($domains);
        foreach ($domains as $baseUrl => $urlConfig) {
            if (mb_strlen($baseUrl) > mb_strlen($lastBaseUrl)) {
                $bestMatch = $urlConfig;
                $lastBaseUrl = $baseUrl;
            }
        }

        $bestMatch['url'] = rtrim($bestMatch['url'], '/');

        return $bestMatch;
    }

    /**
     * @return ResolvedSeoUrl
     */
    private function resolveSeoUrl(Request $request, string $baseUrl, string $languageId, string $channelId): array
    {
        $seoPathInfo = $request->getPathInfo();

        // only remove full base url not part
        // registered domain: 'shop-dev.de/de'
        // incoming request:  'shop-dev.de/detail'
        // without leading slash, detail would be stripped
        $baseUrl = rtrim($baseUrl, '/') . '/';

        if ($this->equalsBaseUrl($seoPathInfo, $baseUrl)) {
            $seoPathInfo = '';
        } elseif ($this->containsBaseUrl($seoPathInfo, $baseUrl)) {
            $seoPathInfo = mb_substr($seoPathInfo, mb_strlen($baseUrl));
        }

        $resolved = $this->resolver->resolve($languageId, $channelId, $seoPathInfo);

        $resolved['pathInfo'] = '/' . ltrim($resolved['pathInfo'], '/');

        return $resolved;
    }

    private function getSchemeAndHttpHost(Request $request): string
    {
        return $request->getScheme() . '://' . idn_to_utf8($request->getHttpHost());
    }

    /**
     * We add the trailing slash to the base url
     * so we have to add it to the path info too, to check if they are equal
     */
    private function equalsBaseUrl(string $seoPathInfo, string $baseUrl): bool
    {
        return $baseUrl === rtrim($seoPathInfo, '/') . '/';
    }

    /**
     * We don't have to add the trailing slash when we check if the pathInfo contains teh base url
     */
    private function containsBaseUrl(string $seoPathInfo, string $baseUrl): bool
    {
        return !empty($baseUrl) && mb_strpos($seoPathInfo, $baseUrl) === 0;
    }
}
