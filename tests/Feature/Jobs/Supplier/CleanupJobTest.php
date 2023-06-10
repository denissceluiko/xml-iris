<?php

namespace Tests\Feature\Jobs\Supplier;

use App\Jobs\Supplier\CleanupJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CleanupJobTest extends TestCase
{
    /**
     * @test
     * @return void
     */
    public function will_delete_provided_file()
    {
        $file = base_path('tests/delete_me_'.md5(rand()));
        file_put_contents($file, 'These contents do not matter.');

        // Storage
        $job = new CleanupJob($file);
        $job->handle();

        $this->assertFileDoesNotExist($file);
    }
}
