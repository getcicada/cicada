<?php declare(strict_types=1);

namespace Cicada\Core\Content\Sitemap\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\System\Channel\ChannelContext;

#[Package('services-settings')]
class AlreadyLockedException extends CicadaHttpException
{
    public function __construct(ChannelContext $channelContext)
    {
        parent::__construct('Cannot acquire lock for sales channel {{channelId}} and language {{languageId}}', [
            'channelId' => $channelContext->getChannel()->getId(),
            'languageId' => $channelContext->getLanguageId(),
        ]);
    }

    public function getErrorCode(): string
    {
        return 'CONTENT__SITEMAP_ALREADY_LOCKED';
    }
}
