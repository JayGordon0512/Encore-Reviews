<?php

declare(strict_types=1);

namespace Tests\Unit;

use JsonException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ProviderApiV2ContractFixtureTest extends TestCase
{
    #[Test]
    public function proposed_contract_and_immutable_fixtures_are_internally_consistent(): void
    {
        $fixtureRoot = dirname(__DIR__).'/Fixtures/ProviderApiV2';
        $manifest = $this->decodeJsonFile($fixtureRoot.'/manifest.json');
        $credentialDocument = $this->decodeJsonFile(
            $fixtureRoot.'/'.$manifest['credential_file'],
        );
        $credentials = [];

        foreach ($credentialDocument['credentials'] as $credential) {
            $credentials[$credential['key_id']] = $credential;
        }

        self::assertSame('2.0.0-proposed.1', $manifest['fixture_version']);
        self::assertSame($manifest['fixture_version'], $manifest['contract_version']);
        self::assertSame('HMAC-SHA256', $manifest['signature_algorithm']);
        self::assertSame('v1=', $manifest['signature_prefix']);
        self::assertCount(15, $manifest['cases']);

        $contractPath = $fixtureRoot.'/'.$manifest['contract_file'];
        self::assertFileExists($contractPath);
        $contract = file_get_contents($contractPath);
        self::assertIsString($contract);
        self::assertStringContainsString('version: 2.0.0-proposed.1', $contract);
        self::assertStringContainsString('/integrations/review-invitation-eligibilities:', $contract);
        self::assertStringContainsString('/integrations/review-invitation-withdrawals:', $contract);

        $caseIds = [];
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
            self::assertArrayNotHasKey($case['id'], $caseIds, "Duplicate fixture ID: {$case['id']}");
            self::assertArrayNotHasKey($case['sequence'], $sequences, "Duplicate fixture sequence: {$case['sequence']}");
            $caseIds[$case['id']] = true;
            $sequences[$case['sequence']] = true;

            foreach ($requiredHeaders as $header) {
                self::assertArrayHasKey($header, $case['headers'], "{$case['id']} is missing {$header}");
            }

            $bodyPath = $fixtureRoot.'/'.$case['body_file'];
            $responsePath = $fixtureRoot.'/'.$case['expected_response_file'];
            self::assertFileExists($bodyPath);
            self::assertFileExists($responsePath);

            $body = file_get_contents($bodyPath);
            self::assertIsString($body);
            $bodyJson = $this->decodeJson($body, $bodyPath);
            $responseJson = $this->decodeJsonFile($responsePath);
            $digest = hash('sha256', $body);

            self::assertSame('2.0', $bodyJson['schema_version'] ?? null, "{$case['id']} has the wrong schema version");
            self::assertSame($case['expected_body_bytes'], strlen($body), "{$case['id']} body bytes changed");
            self::assertSame($case['expected_body_sha256'], $digest, "{$case['id']} body digest changed");
            self::assertStringStartsWith('/api/v2/', $case['path'], "{$case['id']} signs a non-v2 path");
            self::assertSame(
                $case['headers']['X-Correlation-Id'],
                $responseJson['correlation_id'] ?? null,
                "{$case['id']} response does not echo its correlation ID",
            );

            $keyId = $case['headers']['X-Provider-Key-Id'];
            $signatureMatches = false;

            if (isset($credentials[$keyId])) {
                $canonical = implode("\n", [
                    $case['method'],
                    $case['path'],
                    $case['headers']['X-Request-Timestamp'],
                    $case['headers']['X-Request-Nonce'],
                    $digest,
                ]);
                $calculated = 'v1='.hash_hmac('sha256', $canonical, $credentials[$keyId]['secret']);
                $signatureMatches = hash_equals($calculated, $case['headers']['X-Request-Signature']);
            }

            self::assertSame(
                $case['signature_should_verify'],
                $signatureMatches,
                "{$case['id']} signature expectation changed",
            );
        }

        $sequenceNumbers = array_map('intval', array_keys($sequences));
        sort($sequenceNumbers);
        self::assertSame(range(1, count($manifest['cases'])), $sequenceNumbers);
    }

    /** @return array<string, mixed> */
    private function decodeJsonFile(string $path): array
    {
        self::assertFileExists($path);
        $json = file_get_contents($path);
        self::assertIsString($json);

        return $this->decodeJson($json, $path);
    }

    /** @return array<string, mixed> */
    private function decodeJson(string $json, string $path): array
    {
        try {
            $decoded = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            self::fail("Invalid JSON in {$path}: {$exception->getMessage()}");
        }

        self::assertIsArray($decoded);

        return $decoded;
    }
}
