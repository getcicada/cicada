<?php declare(strict_types=1);

namespace Cicada\Core\Content\LandingPage\Exception;

use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;
use Symfony\Component\HttpFoundation\Response;

/**
 * @deprecated tag:v6.7.0 - Will be removed. Use \Cicada\Core\Content\LandingPage\LandingPageException::notFound instead
 */
#[Package('frontend')]
class LandingPageNotFoundException extends CicadaHttpException
{
    public function __construct(string $landingPageId)
    {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            Feature::deprecatedClassMessage(self::class, 'v6.7.0.0', 'LandingPageException::notFound')
        );
        parent::__construct(
            'Landing page "{{ landingPageId }}" not found.',
            ['landingPageId' => $landingPageId]
        );
    }

    public function getStatusCode(): int
    {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            Feature::deprecatedClassMessage(self::class, 'v6.7.0.0', 'LandingPageException::notFound')
        );

        return Response::HTTP_NOT_FOUND;
    }

    public function getErrorCode(): string
    {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            Feature::deprecatedClassMessage(self::class, 'v6.7.0.0', 'LandingPageException::notFound')
        );

        return 'CONTENT__LANDING_PAGE_NOT_FOUND';
    }
}
