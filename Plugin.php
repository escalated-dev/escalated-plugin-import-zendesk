<?php

if (! defined('ESCALATED_LOADED')) {
    exit('Direct access not allowed.');
}

require_once __DIR__ . '/src/ZendeskImportAdapter.php';
require_once __DIR__ . '/src/ZendeskClient.php';
require_once __DIR__ . '/src/ZendeskFieldMapper.php';

use Escalated\Plugins\ImportZendesk\ZendeskImportAdapter;

escalated_add_filter('import.adapters', function (array $adapters) {
    $adapters[] = new ZendeskImportAdapter();
    return $adapters;
}, 10);
