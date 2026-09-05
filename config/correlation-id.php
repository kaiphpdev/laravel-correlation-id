<?php

use LaravelCorrelationId\Generators\UuidGenerator;

return [
    'header' => 'X-Correlation-ID',
    'request_attribute' => 'correlation_id',
    'generator' => UuidGenerator::class,
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
        'trace_payload_key' => 'trace_id',
        'trace_flags_payload_key' => 'trace_flags',
    ],
    'w3c' => [
        'enabled' => false,
        'accept_traceparent' => true,
        'propagate_traceparent' => true,
    ],
];
