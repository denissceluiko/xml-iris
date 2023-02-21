<?php

namespace Tests\Feature\Jobs;

use App\Jobs\Exporter\UpdateFilePathEntryJob;
use App\Models\Export;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UpdateFilePathEntryJobTest extends TestCase
{
    use RefreshDatabase;

    public function setUp() : void
    {
        parent::setUp();

        config()->set('filesystems.disks.export.root', base_path('tests/data/export'));
    }

    /**
     * @test
     * @return void
     */
    public function can_update_export_file_path()
    {
        $export = Export::factory()
                    ->path('old_export_file.xml')
                    ->create();

        $job = new UpdateFilePathEntryJob($export, 'new_export_file.xml');
        $job->handle();

        $this->assertDatabaseHas('exports', [
            'id' => $export->id,
            'path' => 'new_export_file.xml'
        ]);
    }

    /**
     * @test
     * @return void
     */
    public function can_handle_empty_file_path_in_database()
    {
        $export = Export::factory()
                    ->path('')
                    ->create();

        $job = new UpdateFilePathEntryJob($export, 'new_export_file.xml');
        $job->handle();

        $this->assertDatabaseHas('exports', [
            'id' => $export->id,
            'path' => 'new_export_file.xml'
        ]);
    }

    /**
     * @test
     * @return void
     */
    public function can_delete_old_export_file()
    {
        $oldPath = 'old_export_file.xml';
        $newPath = 'new_export_file.xml';

        Storage::disk('export')->put($oldPath, '<data>This file should be deleted</data>');

        $export = Export::factory()
                    ->path('old_export_file.xml')
                    ->create();

        $job = new UpdateFilePathEntryJob($export, $newPath);
        $job->handle();

        $this->assertDatabaseHas('exports', [
            'id' => $export->id,
            'path' => $newPath,
        ]);

        $this->assertFileDoesNotExist(Storage::disk('export')->path($oldPath));
    }
}
