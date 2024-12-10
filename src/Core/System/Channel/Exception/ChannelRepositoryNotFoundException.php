<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;

#[Package('frontend')]
class ChannelRepositoryNotFoundException extends CicadaHttpException
{
    public function __construct(string $entity)
    {
        parent::__construct(
            'ChannelRepository for entity "{{ entityName }}" does not exist.',
            ['entityName' => $entity]
        );
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__SALES_CHANNEL_REPOSITORY_NOT_FOUND';
    }
}
