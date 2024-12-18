<?php declare(strict_types=1);

namespace Cicada\Core\System\NumberRange\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;
use Symfony\Component\HttpFoundation\Response;

#[Package('member')]
class NoConfigurationException extends CicadaHttpException
{
    public function __construct(
        string $entityName,
        ?string $channelId = null
    ) {
        parent::__construct(
            'No number range configuration found for entity "{{ entity }}" with sales channel "{{ channelId }}".',
            ['entity' => $entityName, 'channelId' => $channelId]
        );
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__NO_NUMBER_RANGE_CONFIGURATION';
    }
}
