<?php

return [
    'ticketpal' => [
        'secret' => env('ENCORE_TICKETPAL_SECRET'),
        'signature_tolerance_seconds' => (int) env('ENCORE_TICKETPAL_SIGNATURE_TOLERANCE', 300),
        'max_event_attempts' => (int) env('ENCORE_TICKETPAL_MAX_EVENT_ATTEMPTS', 3),
        'response_retention_seconds' => (int) env('ENCORE_TICKETPAL_RESPONSE_RETENTION', 604800),
    ],
];
