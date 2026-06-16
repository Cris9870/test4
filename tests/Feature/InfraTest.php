<?php

namespace Tests\Feature;

use App\Jobs\PingJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class InfraTest extends TestCase
{
    use RefreshDatabase;

    public function test_infra_devuelve_estado_en_json(): void
    {
        $this->getJson('/infra')
            ->assertOk()
            ->assertJsonStructure([
                'now', 'queue_connection', 'cache_store', 'scheduler_last_run', 'queue_last_job',
            ]);
    }

    public function test_dispatch_encola_el_pingjob(): void
    {
        Queue::fake();

        $this->getJson('/infra/dispatch')
            ->assertOk()
            ->assertJsonStructure(['dispatched', 'at']);

        Queue::assertPushed(PingJob::class);
    }
}
