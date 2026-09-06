<?php

return [
    'provider_v2' => [
        'ingress_enabled' => (bool) env('ENCORE_PROVIDER_V2_INGRESS_ENABLED', false),
        'invitation_issuing_enabled' => (bool) env('ENCORE_PROVIDER_V2_INVITATION_ISSUING_ENABLED', false),
        'signature_tolerance_seconds' => (int) env('ENCORE_PROVIDER_V2_SIGNATURE_TOLERANCE', 300),
        'nonce_retention_seconds' => (int) env('ENCORE_PROVIDER_V2_NONCE_RETENTION', 900),
        'invitation_delay_hours' => (int) env('ENCORE_PROVIDER_V2_INVITATION_DELAY_HOURS', 1),
        'contact_fingerprint_key' => env('ENCORE_CONTACT_FINGERPRINT_KEY'),
        'contact_fingerprint_version' => (int) env('ENCORE_CONTACT_FINGERPRINT_VERSION', 1),
        'catalogue_credentials' => [
            'staging' => [
                'key_id' => env('ENCORE_PROVIDER_V2_TICKETPAL_CATALOGUE_STAGING_KEY_ID'),
                'secret_reference' => 'ticketpal-catalogue-staging',
            ],
            'production' => [
                'key_id' => env('ENCORE_PROVIDER_V2_TICKETPAL_CATALOGUE_PRODUCTION_KEY_ID'),
                'secret_reference' => 'ticketpal-catalogue-production',
            ],
        ],
        'secret_references' => [
            'ticketpal-catalogue-staging' => env('ENCORE_PROVIDER_V2_TICKETPAL_CATALOGUE_STAGING_SECRET'),
            'ticketpal-catalogue-production' => env('ENCORE_PROVIDER_V2_TICKETPAL_CATALOGUE_PRODUCTION_SECRET'),
        ],
    ],

    'invitations' => [
        'token_digest_key' => env('ENCORE_INVITATION_TOKEN_DIGEST_KEY'),
        'previous_token_digest_keys' => array_values(array_filter(explode(',', (string) env('ENCORE_INVITATION_PREVIOUS_TOKEN_DIGEST_KEYS', '')))),
        'expiry_days' => (int) env('ENCORE_INVITATION_EXPIRY_DAYS', 7),
        'max_attempts' => (int) env('ENCORE_INVITATION_MAX_ATTEMPTS', 3),
        'retry_delay_minutes' => (int) env('ENCORE_INVITATION_RETRY_DELAY_MINUTES', 15),
        'claim_timeout_minutes' => (int) env('ENCORE_INVITATION_CLAIM_TIMEOUT_MINUTES', 5),
        'default_event_duration_minutes' => (int) env('ENCORE_DEFAULT_EVENT_DURATION_MINUTES', 150),
    ],

    'audience_imports' => [
        'contact_fingerprint_key' => env('ENCORE_CONTACT_FINGERPRINT_KEY'),
        'contact_fingerprint_version' => (int) env('ENCORE_CONTACT_FINGERPRINT_VERSION', 1),
        'max_rows' => (int) env('ENCORE_AUDIENCE_IMPORT_MAX_ROWS', 1000),
        'invitation_issuing_enabled' => (bool) env('ENCORE_ORGANISER_INVITATION_ISSUING_ENABLED', false),
        'invitation_delay_hours' => (int) env('ENCORE_ORGANISER_INVITATION_DELAY_HOURS', 1),
    ],

    'event_images' => [
        'disk' => env('ENCORE_EVENT_IMAGE_DISK', 'public'),
        'max_size_kb' => (int) env('ENCORE_EVENT_IMAGE_MAX_SIZE_KB', 5120),
    ],

    'mailgun_webhooks' => [
        'enabled' => (bool) env('ENCORE_MAILGUN_WEBHOOKS_ENABLED', false),
        'signing_key' => env('MAILGUN_WEBHOOK_SIGNING_KEY'),
        'signature_tolerance_seconds' => (int) env('ENCORE_MAILGUN_WEBHOOK_SIGNATURE_TOLERANCE', 300),
    ],

    'ticketpal' => [
        'organiser_login_url' => env('ENCORE_TICKETPAL_ORGANISER_LOGIN_URL', 'https://ticketpal.co.uk/login'),
        'secret' => env('ENCORE_TICKETPAL_SECRET'),
        'signature_tolerance_seconds' => (int) env('ENCORE_TICKETPAL_SIGNATURE_TOLERANCE', 300),
        'max_event_attempts' => (int) env('ENCORE_TICKETPAL_MAX_EVENT_ATTEMPTS', 3),
        'response_retention_seconds' => (int) env('ENCORE_TICKETPAL_RESPONSE_RETENTION', 604800),
    ],
];
