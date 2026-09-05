<?php

return [
    'header' => 'X-Correlation-ID',
    'request_attribute' => 'correlation_id',
    'trust_incoming' => true,

    'incoming' => [
        'max_length' => 128,
        'pattern' => '/^[A-Za-z0-9._:-]+$/',
    ],

    'logging' => [
        'enabled' => true,
        'key' => 'correlation_id',
    ],
    'http_client' => [
        'enabled' => true,
    ],
    'queue' => [
        'enabled' => true,
        'payload_key' => 'correlation_id',
    ],
];
