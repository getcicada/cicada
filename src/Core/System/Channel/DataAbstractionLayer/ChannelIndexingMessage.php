<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel\DataAbstractionLayer;

use Cicada\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexingMessage;
use Cicada\Core\Framework\Log\Package;

#[Package('frontend')]
class ChannelIndexingMessage extends EntityIndexingMessage
{
}
