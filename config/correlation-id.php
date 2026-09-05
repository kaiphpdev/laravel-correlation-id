<?php

return [
    'header' => 'X-Correlation-ID',
    'trust_incoming' => true,

    'logging' => [
        'enabled' => true,
        'key' => 'correlation_id',
    ],
];
