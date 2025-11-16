<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use Tests\TestCase;

class EnvironmentAndConfigTest extends TestCase
{
    public function test_env_files_exist(): void
    {
        $files = new Filesystem;

        $this->assertTrue($files->exists(base_path('.env.development')));
        $this->assertTrue($files->exists(base_path('.env.staging')));
        $this->assertTrue($files->exists(base_path('.env.production')));
    }

    public function test_sanctum_config_present(): void
    {
        $this->assertIsArray(config('sanctum'));
        $this->assertSame(['web'], config('sanctum.guard'));
    }

    public function test_scout_config_present_and_defaults(): void
    {
        $this->assertIsArray(config('scout'));
        $this->assertIsArray(config('scout.meilisearch'));
        $this->assertSame('http://127.0.0.1:7700', config('scout.meilisearch.host'));
    }

    public function test_filesystems_s3_disk_has_url_key(): void
    {
        $s3 = config('filesystems.disks.s3');

        $this->assertIsArray($s3);
        $this->assertArrayHasKey('url', $s3);
    }
}
