<?php declare(strict_types=1);

namespace Cicada\Core\Test\Stub\SystemConfigService;

use Cicada\Core\System\SystemConfig\SystemConfigService;

/**
 * @final
 */
class StaticSystemConfigService extends SystemConfigService
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(private array $config = [])
    {
    }

    public function get(string $key, ?string $channelId = null)
    {
        if ($channelId) {
            return $this->lookupValue($this->config[$channelId] ?? [], $key);
        }

        return $this->lookupValue($this->config, $key);
    }

    public function set(string $key, $value, ?string $channelId = null): void
    {
        if ($channelId) {
            $this->config[$channelId][$key] = $value;

            return;
        }

        $this->config[$key] = $value;
    }

    public function setMultiple(array $values, ?string $channelId = null): void
    {
        foreach ($values as $k => $v) {
            $this->set($k, $v, $channelId);
        }
    }

    /**
     * @param array<string, mixed> $param
     */
    private function lookupValue(array $param, string $key): mixed
    {
        if (\array_key_exists($key, $param)) {
            return $param[$key];
        }

        // Look for sub keys
        $foundValues = [];
        $prefix = rtrim($key, '.');
        foreach ($param as $configKey => $configValue) {
            if (!str_starts_with($configKey, $prefix)) {
                continue;
            }

            $formattedKey = substr($configKey, \strlen($prefix) + 1);

            $pointer = &$foundValues;
            foreach (explode('.', $formattedKey) as $part) {
                // @phpstan-ignore-next-line
                if (!\array_key_exists($part, $pointer)) {
                    $pointer[$part] = [];
                }

                $pointer = &$pointer[$part];
            }
            $pointer = $configValue;
        }

        // @phpstan-ignore-next-line
        if (empty($foundValues)) {
            return null;
        }

        // @phpstan-ignore-next-line
        return $foundValues;
    }
}
