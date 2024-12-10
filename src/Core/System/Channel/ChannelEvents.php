<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\Event\ChannelIndexerEvent;

#[Package('frontend')]
class ChannelEvents
{
    final public const SALES_CHANNEL_WRITTEN = 'channel.written';

    final public const SALES_CHANNEL_DELETED = 'channel.deleted';

    final public const SALES_CHANNEL_LOADED = 'channel.loaded';

    final public const SALES_CHANNEL_INDEXER_EVENT = ChannelIndexerEvent::class;

    final public const SALES_CHANNEL_SEARCH_RESULT_LOADED = 'channel.search.result.loaded';

    final public const SALES_CHANNEL_AGGREGATION_RESULT_LOADED = 'channel.aggregation.result.loaded';

    final public const SALES_CHANNEL_ID_SEARCH_RESULT_LOADED = 'channel.id.search.result.loaded';

    final public const SALES_CHANNEL_TRANSLATION_WRITTEN_EVENT = 'channel_translation.written';

    final public const SALES_CHANNEL_TRANSLATION_DELETED_EVENT = 'channel_translation.deleted';

    final public const SALES_CHANNEL_TRANSLATION_LOADED_EVENT = 'channel_translation.loaded';

    final public const SALES_CHANNEL_TRANSLATION_SEARCH_RESULT_LOADED_EVENT = 'channel_translation.search.result.loaded';

    final public const SALES_CHANNEL_TRANSLATION_AGGREGATION_LOADED_EVENT = 'channel_translation.aggregation.result.loaded';

    final public const SALES_CHANNEL_TRANSLATION_ID_SEARCH_RESULT_LOADED_EVENT = 'channel_translation.id.search.result.loaded';

    final public const SALES_CHANNEL_TYPE_WRITTEN = 'channel_type.written';

    final public const SALES_CHANNEL_TYPE_DELETED = 'channel_type.deleted';

    final public const SALES_CHANNEL_TYPE_LOADED = 'channel_type.loaded';

    final public const SALES_CHANNEL_TYPE_SEARCH_RESULT_LOADED = 'channel_type.search.result.loaded';

    final public const SALES_CHANNEL_TYPE_AGGREGATION_RESULT_LOADED = 'channel_type.aggregation.result.loaded';

    final public const SALES_CHANNEL_TYPE_ID_SEARCH_RESULT_LOADED = 'channel_type.id.search.result.loaded';

    final public const SALES_CHANNEL_TYPE_TRANSLATION_WRITTEN_EVENT = 'channel_type_translation.written';

    final public const SALES_CHANNEL_TYPE_TRANSLATION_DELETED_EVENT = 'channel_type_translation.deleted';

    final public const SALES_CHANNEL_TYPE_TRANSLATION_LOADED_EVENT = 'channel_type_translation.loaded';

    final public const SALES_CHANNEL_TYPE_TRANSLATION_SEARCH_RESULT_LOADED_EVENT = 'channel_type_translation.search.result.loaded';

    final public const SALES_CHANNEL_TYPE_TRANSLATION_AGGREGATION_LOADED_EVENT = 'channel_type_translation.aggregation.result.loaded';

    final public const SALES_CHANNEL_TYPE_TRANSLATION_ID_SEARCH_RESULT_LOADED_EVENT = 'channel_type_translation.id.search.result.loaded';
}
