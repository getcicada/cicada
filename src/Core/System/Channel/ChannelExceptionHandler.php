<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel;

use Cicada\Core\Framework\DataAbstractionLayer\Dbal\ExceptionHandlerInterface;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\Exception\LanguageOfChannelDomainDeleteException;

#[Package('frontend')]
class ChannelExceptionHandler implements ExceptionHandlerInterface
{
    public function getPriority(): int
    {
        return ExceptionHandlerInterface::PRIORITY_DEFAULT;
    }

    public function matchException(\Exception $e): ?\Exception
    {
        if (preg_match('/SQLSTATE\[23000\]:.*1451.*a foreign key constraint.*channel_domain.*CONSTRAINT `fk.channel_domain.language_id`/', $e->getMessage())) {
            return new LanguageOfChannelDomainDeleteException($e);
        }

        if (preg_match('/SQLSTATE\[23000\]:.*1451.*a foreign key constraint.*product_export.*CONSTRAINT `fk.product_export.channel_domain_id`/', $e->getMessage())) {
            return ChannelException::channelDomainInUse($e);
        }

        return null;
    }
}
