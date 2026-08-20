<?php

return [
    'provider_v2' => [
        'ingress_enabled' => (bool) env('ENCORE_PROVIDER_V2_INGRESS_ENABLED', false),
        'invitation_issuing_enabled' => (bool) env('ENCORE_PROVIDER_V2_INVITATION_ISSUING_ENABLED', false),
        'signature_tolerance_seconds' => (int) env('ENCORE_PROVIDER_V2_SIGNATURE_TOLERANCE', 300),
        'nonce_retention_seconds' => (int) env('ENCORE_PROVIDER_V2_NONCE_RETENTION', 900),
        'invitation_delay_hours' => (int) env('ENCORE_PROVIDER_V2_INVITATION_DELAY_HOURS', 2),
        'contact_fingerprint_key' => env('ENCORE_CONTACT_FINGERPRINT_KEY'),
        'contact_fingerprint_version' => (int) env('ENCORE_CONTACT_FINGERPRINT_VERSION', 1),
        'secret_references' => [],
    ],

    'ticketpal' => [
        'secret' => env('ENCORE_TICKETPAL_SECRET'),
        'signature_tolerance_seconds' => (int) env('ENCORE_TICKETPAL_SIGNATURE_TOLERANCE', 300),
        'max_event_attempts' => (int) env('ENCORE_TICKETPAL_MAX_EVENT_ATTEMPTS', 3),
        'response_retention_seconds' => (int) env('ENCORE_TICKETPAL_RESPONSE_RETENTION', 604800),
    ],
];
