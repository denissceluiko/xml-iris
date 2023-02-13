<?php

namespace Tests\Feature\Service\Supplier;

use App\Models\Supplier;
use App\Services\Supplier\PullService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PullServiceTest extends TestCase
{
    use RefreshDatabase;

    public function setUp() : void
    {
        parent::setUp();

        config()->set('filesystems.disks.local.root', base_path('tests/data'));
    }

    /**
     * @test
     * @return void
     */
    public function can_pull_local_file()
    {
        $supplier = Supplier::factory()
                        ->uri('supplier_import_simple.xml')
                        ->create();

        $service = new PullService($supplier);
        $result = $service->pull();

        $this->assertEquals(Storage::get('supplier_import_simple.xml'), $result);
    }


    /**
     * @test
     * @return void
     */
    public function can_pull_remote_file()
    {
        $supplier = Supplier::factory()
                        ->uri('https://example.com/supplier_export.xml')
                        ->create();

        Http::fake([
            'example.com/*' => Http::response("Just a remote response")
        ]);

        $service = new PullService($supplier);
        $result = $service->pull();

        $this->assertEquals("Just a remote response", $result);
    }
}
