<?php

declare(strict_types=1);

namespace App\Neuron\Workflows\EmailQuery\Helper;

enum NodeState: string
{
    case QUERY_IN_PROGRESS = 'running_query';
    case QUERY_OBTAINED = 'query_obtained';
    case DELEGATING = 'delegating';
    case QUERY_RESPONSE_TRANSLATIONS_REQUESTED = 'query_response_translations_requested';
    case QUERY_RESPONSE_TRANSLATING = 'query_response_translating';
    case QUERY_RESPONSE_TRANSLATED = 'query_response_translated';
    case COLLECTING_TRANSLATIONS = 'collecting_translations';

    case COLLECTED_TRANSLATIONS = 'collected_translations';
    case TRANSLATIONS_PUSHED = 'translations_pushed';
}
