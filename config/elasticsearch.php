<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Hosts
    |--------------------------------------------------------------------------
    |
    | A comma-separated list of Elasticsearch node URLs.
    |
    */

    'hosts' => explode(',', env('ELASTICSEARCH_HOSTS', 'http://localhost:9200')),

    /*
    |--------------------------------------------------------------------------
    | Ticket index name
    |--------------------------------------------------------------------------
    */

    'ticket_index' => env('ELASTICSEARCH_TICKET_INDEX', 'tickets'),

];
