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

    public function test_staging_compose_supervises_web_invitations_and_the_scheduler(): void
    {
        $configuration = file_get_contents(dirname(__DIR__, 2).'/deploy/staging/compose.yml');

        $this->assertStringContainsString('127.0.0.1:8081:80', $configuration);
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
}
