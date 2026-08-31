<?php

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;

class StagingDeploymentTest extends TestCase
{
    public function test_staging_ingress_only_proxies_to_the_loopback_container_port(): void
    {
        $configuration = file_get_contents(dirname(__DIR__, 2).'/deploy/staging/host-nginx.conf');

        $this->assertStringContainsString('server_name staging.encorereviews.co.uk;', $configuration);
        $this->assertStringContainsString('proxy_pass http://127.0.0.1:8081;', $configuration);
        $this->assertStringNotContainsString('proxy_pass http://0.0.0.0', $configuration);
    }

    public function test_staging_compose_supervises_database_web_invitations_and_the_scheduler(): void
    {
        $configuration = file_get_contents(dirname(__DIR__, 2).'/deploy/staging/compose.yml');

        $this->assertStringContainsString('127.0.0.1:8081:80', $configuration);
        $this->assertStringContainsString('image: postgres:17-alpine', $configuration);
        $this->assertStringContainsString('database-data:/var/lib/postgresql/data', $configuration);
        $this->assertStringContainsString('DB_HOST: database', $configuration);
        $this->assertStringContainsString('DB_PASSWORD: ${POSTGRES_PASSWORD:?POSTGRES_PASSWORD must be set}', $configuration);
        $this->assertStringContainsString('php artisan queue:work database --queue=invitations', $configuration);
        $this->assertStringContainsString('php artisan schedule:work', $configuration);
        $this->assertStringNotContainsString('ports:'.PHP_EOL.'      - 5432', $configuration);
    }

    public function test_staging_example_keeps_invitation_issuing_disabled(): void
    {
        $configuration = file_get_contents(dirname(__DIR__, 2).'/deploy/staging/env.example');

        $this->assertStringContainsString('ENCORE_PROVIDER_V2_INVITATION_ISSUING_ENABLED=false', $configuration);
        $this->assertStringContainsString('ENCORE_ORGANISER_INVITATION_ISSUING_ENABLED=false', $configuration);
        $this->assertStringContainsString('QUEUE_CONNECTION=database', $configuration);
        $this->assertStringContainsString('ENCORE_EVENT_IMAGE_DISK=s3', $configuration);
    }

    public function test_staging_database_backup_is_verified_and_uploaded_to_spaces(): void
    {
        $script = file_get_contents(dirname(__DIR__, 2).'/deploy/staging/backup-encore-staging');
        $timer = file_get_contents(dirname(__DIR__, 2).'/deploy/staging/encore-staging-backup.timer');

        $this->assertStringContainsString('pg_dump', $script);
        $this->assertStringContainsString('pg_restore --list', $script);
        $this->assertStringContainsString('aws s3 cp', $script);
        $this->assertStringContainsString('database-backups/', $script);
        $this->assertStringContainsString('OnCalendar=*-*-* 03:15:00 UTC', $timer);
        $this->assertStringContainsString('Persistent=true', $timer);
    }
}
