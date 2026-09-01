<?php

namespace App\Application\Audience;

use App\Application\Invitations\DetermineInvitationScheduleTime;
use App\Models\AudienceAttendance;
use App\Models\AudienceImport;
use App\Models\Performance;
use App\Models\ProtectedReviewerContact;
use App\Models\ReviewInvitationSchedule;
use App\Models\Show;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

final class ImportOrganiserAudienceService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly DetermineInvitationScheduleTime $scheduleTime,
    ) {}

    public function import(
        User $actor,
        Show $show,
        Performance $performance,
        UploadedFile $file,
        ?string $ipAddress,
        ?string $userAgent,
    ): AudienceImport {
        $rows = $this->readRows($file);
        $fingerprintKey = config('encore.audience_imports.contact_fingerprint_key');
        if (! is_string($fingerprintKey) || $fingerprintKey === '') {
            throw new RuntimeException('Secure audience contact storage is not configured.');
        }

        $correlationId = (string) Str::uuid();
        $version = (int) config('encore.audience_imports.contact_fingerprint_version');
        $sourceFileName = basename($file->getClientOriginalName()) ?: 'customers.csv';

        return DB::transaction(function () use (
            $actor,
            $show,
            $performance,
            $rows,
            $fingerprintKey,
            $version,
            $sourceFileName,
            $correlationId,
            $ipAddress,
            $userAgent,
        ): AudienceImport {
            $audienceImport = AudienceImport::create([
                'organisation_id' => $show->organisation_id,
                'show_id' => $show->id,
                'performance_id' => $performance->id,
                'imported_by' => $actor->id,
                'source_file_name' => Str::limit($sourceFileName, 255, ''),
                'rows_total' => count($rows),
                'rows_imported' => 0,
                'rows_skipped' => 0,
                'status' => 'processing',
                'attendance_confirmed_at' => now(),
                'correlation_id' => $correlationId,
            ]);

            $imported = 0;
            $skipped = 0;

            foreach ($rows as $row) {
                if ($row['email'] === null) {
                    $skipped++;

                    continue;
                }

                $fingerprint = hash_hmac('sha256', $row['email'], $fingerprintKey);
                $contact = ProtectedReviewerContact::query()->firstOrCreate(
                    ['fingerprint_version' => $version, 'email_fingerprint' => $fingerprint],
                    [
                        'email_ciphertext' => Crypt::encryptString($row['email']),
                        'display_name_ciphertext' => Crypt::encryptString($row['name']),
                        'status' => 'active',
                    ],
                );

                $attendance = AudienceAttendance::query()->firstOrCreate(
                    ['performance_id' => $performance->id, 'contact_id' => $contact->id],
                    [
                        'organisation_id' => $show->organisation_id,
                        'show_id' => $show->id,
                        'audience_import_id' => $audienceImport->id,
                        'source' => 'organiser_csv',
                        'attendance_state' => 'organiser_confirmed',
                        'status' => 'active',
                    ],
                );

                if ($attendance->wasRecentlyCreated) {
                    $scheduledFor = $this->scheduleTime->forPerformance(
                        $performance,
                        (int) config('encore.audience_imports.invitation_delay_hours'),
                    );
                    if ($scheduledFor->isPast()) {
                        $scheduledFor = now();
                    }
                    $issuingEnabled = (bool) config('encore.audience_imports.invitation_issuing_enabled');
                    ReviewInvitationSchedule::create([
                        'audience_attendance_id' => $attendance->id,
                        'source' => 'organiser_csv',
                        'correlation_id' => $correlationId,
                        'scheduled_for' => $scheduledFor,
                        'status' => $issuingEnabled ? 'scheduled' : 'suppressed',
                        'suppression_reason' => $issuingEnabled
                            ? null
                            : 'organiser_invitation_issuing_disabled',
                    ]);
                    $imported++;
                } else {
                    $skipped++;
                }
            }

            if ($imported === 0) {
                throw ValidationException::withMessages([
                    'customers_csv' => 'The CSV did not contain any new valid customer email addresses for this date.',
                ]);
            }

            $audienceImport->update([
                'rows_imported' => $imported,
                'rows_skipped' => $skipped,
                'status' => 'completed',
            ]);

            $this->auditLogger->record(
                $actor,
                'audience.csv_imported',
                $audienceImport,
                $show->organisation_id,
                null,
                [
                    'show_id' => $show->id,
                    'performance_id' => $performance->id,
                    'rows_total' => count($rows),
                    'rows_imported' => $imported,
                    'rows_skipped' => $skipped,
                    'source' => 'organiser_csv',
                    'attendance_state' => 'organiser_confirmed',
                ],
                $ipAddress,
                $userAgent,
                $correlationId,
            );

            return $audienceImport->refresh();
        });
    }

    /** @return list<array{email: ?string, name: string}> */
    private function readRows(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'rb');
        if ($handle === false) {
            throw ValidationException::withMessages(['customers_csv' => 'The CSV could not be read.']);
        }

        try {
            $header = fgetcsv($handle);
            if (! is_array($header)) {
                throw ValidationException::withMessages(['customers_csv' => 'The CSV is empty.']);
            }

            $header = array_map(function (mixed $value): string {
                $normalized = Str::lower(trim((string) $value));

                return preg_replace('/^\xEF\xBB\xBF/', '', $normalized) ?? $normalized;
            }, $header);
            $emailIndex = array_search('email', $header, true);
            $nameIndex = array_search('name', $header, true);
            if ($nameIndex === false) {
                $nameIndex = array_search('full_name', $header, true);
            }
            if ($emailIndex === false) {
                throw ValidationException::withMessages([
                    'customers_csv' => 'The CSV must contain an email column. A name column is optional.',
                ]);
            }

            $rows = [];
            $maxRows = (int) config('encore.audience_imports.max_rows', 1000);
            while (($record = fgetcsv($handle)) !== false) {
                if (collect($record)->every(fn (mixed $value): bool => trim((string) $value) === '')) {
                    continue;
                }
                if (count($rows) >= $maxRows) {
                    throw ValidationException::withMessages([
                        'customers_csv' => "The CSV may contain no more than {$maxRows} customer rows.",
                    ]);
                }

                $email = Str::lower(trim((string) ($record[$emailIndex] ?? '')));
                $rows[] = [
                    'email' => filter_var($email, FILTER_VALIDATE_EMAIL) === false ? null : $email,
                    'name' => trim((string) ($nameIndex === false ? '' : ($record[$nameIndex] ?? ''))),
                ];
            }
        } finally {
            fclose($handle);
        }

        if ($rows === []) {
            throw ValidationException::withMessages(['customers_csv' => 'The CSV does not contain any customer rows.']);
        }

        return $rows;
    }
}
