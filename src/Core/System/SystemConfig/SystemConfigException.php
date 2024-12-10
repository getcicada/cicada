<?php declare(strict_types=1);

namespace Cicada\Core\System\SystemConfig;

use Cicada\Core\Framework\HttpException;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[Package('core')]
class SystemConfigException extends HttpException
{
    public const SYSTEM_MANAGED_SYSTEM_CONFIG = 'SYSTEM__MANAGED_SYSTEM_CONFIG_CANNOT_BE_CHANGED';
    public const INVALID_DOMAIN = 'SYSTEM__INVALID_DOMAIN';
    public const CONFIG_NOT_FOUND = 'SYSTEM__SCOPE_NOT_FOUND';

    public static function systemConfigKeyIsManagedBySystems(string $configKey): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            self::SYSTEM_MANAGED_SYSTEM_CONFIG,
            'The system configuration key "{{ configKey }}" cannot be changed, as it is managed by the Cicada yaml file configuration system provided by Symfony.',
            [
                'configKey' => $configKey,
            ],
        );
    }

    public static function invalidDomain(): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            self::INVALID_DOMAIN,
            'Invalid domain',
        );
    }

    /**
     * @deprecated tag:v6.7.0 - reason:return-type-change - Will only return `self` in the future
     */
    public static function configurationNotFound(string $scope): self
    {
        return new self(
            Response::HTTP_NOT_FOUND,
            self::CONFIG_NOT_FOUND,
            'Configuration for scope "{{ scope }}" not found.',
            ['scope' => $scope]
        );
    }
}