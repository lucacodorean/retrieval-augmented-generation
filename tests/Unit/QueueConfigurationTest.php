<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Jobs\DeleteRagDocument;
use App\Jobs\UpsertRagDocument;
use Tests\TestCase;

class QueueConfigurationTest extends TestCase
{
    public function test_testing_environment_persists_queued_jobs_without_running_them(): void
    {
        $this->assertSame('database', config('queue.default'));
    }

    public function test_development_queue_worker_retries_transient_failures_three_times(): void
    {
        $composer = json_decode((string) file_get_contents(base_path('composer.json')), true, flags: JSON_THROW_ON_ERROR);

        $this->assertStringContainsString('php artisan queue:listen --tries=3 --timeout=0', $composer['scripts']['dev'][1]);
    }

    public function test_document_jobs_define_a_portable_retry_policy(): void
    {
        $jobs = [
            new UpsertRagDocument('model-class', 1),
            new DeleteRagDocument('model-class', 'document-key'),
        ];

        foreach ($jobs as $job) {
            $this->assertSame(3, $job->tries);
            $this->assertSame([10, 60], $job->backoff);
        }
    }
}
