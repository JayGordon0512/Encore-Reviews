<?php

declare(strict_types=1);

$root = __DIR__;
$manifest = json_decode(file_get_contents($root.'/manifest.json'), true, flags: JSON_THROW_ON_ERROR);
$credentialDocument = json_decode(
    file_get_contents($root.'/'.$manifest['credential_file']),
    true,
    flags: JSON_THROW_ON_ERROR,
);
$credentials = [];
foreach ($credentialDocument['credentials'] as $credential) {
    $credentials[$credential['key_id']] = $credential;
}

$failures = [];
$ids = [];
$sequences = [];
$requiredHeaders = [
    'Content-Type',
    'X-Provider-Key-Id',
    'X-Request-Timestamp',
    'X-Request-Nonce',
    'X-Request-Signature',
    'Idempotency-Key',
    'X-Correlation-Id',
];

foreach ($manifest['cases'] as $case) {
    $id = $case['id'];
    if (isset($ids[$id])) {
        $failures[] = "{$id}: duplicate case ID";
    }
    if (isset($sequences[$case['sequence']])) {
        $failures[] = "{$id}: duplicate sequence";
    }
    $ids[$id] = true;
    $sequences[$case['sequence']] = true;

    foreach ($requiredHeaders as $header) {
        if (! array_key_exists($header, $case['headers'])) {
            $failures[] = "{$id}: missing header {$header}";
        }
    }

    $body = file_get_contents($root.'/'.$case['body_file']);
    $response = file_get_contents($root.'/'.$case['expected_response_file']);
    try {
        $bodyJson = json_decode($body, true, flags: JSON_THROW_ON_ERROR);
        $responseJson = json_decode($response, true, flags: JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        $failures[] = "{$id}: invalid JSON: {$exception->getMessage()}";

        continue;
    }

    if (($bodyJson['schema_version'] ?? null) !== '2.0') {
        $failures[] = "{$id}: body schema_version is not 2.0";
    }
    if (($responseJson['correlation_id'] ?? null) !== $case['headers']['X-Correlation-Id']) {
        $failures[] = "{$id}: response correlation_id does not echo the request";
    }
    if (strlen($body) !== $case['expected_body_bytes']) {
        $failures[] = sprintf('%s: expected %d body bytes, got %d', $id, $case['expected_body_bytes'], strlen($body));
    }

    $digest = hash('sha256', $body);
    if (! hash_equals($case['expected_body_sha256'], $digest)) {
        $failures[] = "{$id}: body SHA-256 mismatch";
    }
    if (! str_starts_with($case['path'], '/api/v2/')) {
        $failures[] = "{$id}: signed path is not an /api/v2 path";
    }

    $keyId = $case['headers']['X-Provider-Key-Id'];
    $matches = false;
    if (isset($credentials[$keyId])) {
        $canonical = implode("\n", [
            $case['method'],
            $case['path'],
            $case['headers']['X-Request-Timestamp'],
            $case['headers']['X-Request-Nonce'],
            $digest,
        ]);
        $calculated = 'v1='.hash_hmac('sha256', $canonical, $credentials[$keyId]['secret']);
        $matches = hash_equals($calculated, $case['headers']['X-Request-Signature']);
    }
    if ($matches !== $case['signature_should_verify']) {
        $failures[] = "{$id}: signature expectation failed";
    }
}

$sequenceNumbers = array_keys($sequences);
sort($sequenceNumbers);
if ($sequenceNumbers !== range(1, count($manifest['cases']))) {
    $failures[] = 'case sequences are not contiguous from 1';
}

if ($failures !== []) {
    foreach ($failures as $failure) {
        fwrite(STDERR, "FAIL {$failure}\n");
    }
    exit(1);
}

printf("OK %d Provider API v2 fixtures verified\n", count($manifest['cases']));
