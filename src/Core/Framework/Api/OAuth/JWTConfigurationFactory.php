<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Api\OAuth;

use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256 as Hmac256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256 as Rsa256;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Cicada\Core\DevOps\Environment\EnvironmentHelper;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('core')]
class JWTConfigurationFactory
{

    public static function createJWTConfiguration(
    ): Configuration {
        return self::createUsingAppSecret();
    }

    public static function createUsingAppSecret(): Configuration
    {
        /** @var non-empty-string $secret */
        $secret = (string) EnvironmentHelper::getVariable('APP_SECRET');
        $key = InMemory::plainText($secret);

        $configuration = Configuration::forSymmetricSigner(
            new Hmac256(),
            $key
        );

        $clock = new SystemClock(new \DateTimeZone(\date_default_timezone_get()));

        $configuration->setValidationConstraints(
            new SignedWith(new Hmac256(), $key),
            new LooseValidAt($clock, null),
        );

        return $configuration;
    }

    /**
     * @param non-empty-string $privateKey
     */
    private static function createKey(string $privateKey, string $keyPassphrase): InMemory
    {
        if (str_starts_with($privateKey, 'file://')) {
            /** @var non-empty-string $path */
            $path = substr($privateKey, 7);

            return InMemory::file($path, $keyPassphrase);
        }

        return InMemory::plainText($privateKey, $keyPassphrase);
    }
}
