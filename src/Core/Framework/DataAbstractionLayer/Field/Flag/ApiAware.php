<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Field\Flag;

use Cicada\Core\Framework\Api\Context\AdminApiSource;
use Cicada\Core\Framework\Api\Context\ChannelApiSource;
use Cicada\Core\Framework\Api\Context\SystemSource;
use Cicada\Core\Framework\Log\Package;

#[Package('core')]
class ApiAware extends Flag
{
    private const BASE_URLS = [
        AdminApiSource::class => '/api/',
        ChannelApiSource::class => '/store-api/',
    ];

    /**
     * @var array<string, string>
     */
    private array $whitelist = [];

    public function __construct(string ...$protectedSources)
    {
        foreach ($protectedSources as $source) {
            $this->whitelist[$source] = self::BASE_URLS[$source];
        }

        if (empty($protectedSources)) {
            $this->whitelist = self::BASE_URLS;
        }
    }

    public function isBaseUrlAllowed(string $baseUrl): bool
    {
        $baseUrl = rtrim($baseUrl, '/') . '/';

        foreach ($this->whitelist as $url) {
            if (mb_strpos($baseUrl, $url) !== false) {
                return true;
            }
        }

        return false;
    }

    public function isSourceAllowed(string $source): bool
    {
        if ($source === SystemSource::class) {
            return true;
        }

        return isset($this->whitelist[$source]);
    }

    public function parse(): \Generator
    {
        yield 'read_protected' => [
            array_keys($this->whitelist),
        ];
    }
}