<?php

namespace Tests\Feature\Jobs\Exporter\XML;

use App\Jobs\Exporter\Xml\CompiledProductExportJob;
use App\Models\CompiledProduct;
use App\Models\Compiler;
use App\Models\Export;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CompiledProductExportJobTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @return void
     */
    public function can_export_xml()
    {
        $filename = 'xml-export.xml';

        $export = Export::factory()
                    ->xml()
                    ->create();

        $job = new CompiledProductExportJob($export, $filename, 'export');

        $job->handle();

        $this->assertTrue(Storage::disk('export')->exists($filename));

        $expected =<<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<products/>

EOF;
        $this->assertEquals($expected, Storage::disk('export')->get($filename));

        Storage::disk('export')->delete($filename);
    }

    /**
     * @test
     * @return void
     */
    public function can_export_products()
    {
        $filename = 'xml-export.xml';

        $fields = [
            'ean' => 'string',
            'price' => 'float',
        ];

        $compiler = Compiler::factory()
                    ->fields($fields)
                    ->has(CompiledProduct::factory()
                        ->data([
                            'ean' => '12345',
                            'price' => 123.45,
                        ])
                        ->count(1)
                    )
                    ->has(Export::factory()
                        ->xml()
                    )
                    ->create();

        $job = new CompiledProductExportJob($compiler->exports->first(), $filename, 'export');

        $job->handle();

        $this->assertTrue(Storage::disk('export')->exists($filename));

        $expected =<<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<products><product><ean>12345</ean><price>123.45</price></product></products>

EOF;
        $this->assertEquals($expected, Storage::disk('export')->get($filename));

        Storage::disk('export')->delete($filename);
    }

    /**
     * @test
     * @return void
     */
    public function can_handle_multi_level_mappings()
    {
        $filename = 'xml-export.xml';

        $fields = [
            'ean' => 'string',
            'price' => 'float',
            'currency' => 'string',
        ];

        $compiler = Compiler::factory()
                    ->fields($fields)
                    ->has(CompiledProduct::factory()
                        ->data([
                            'ean' => '12345',
                            'currency' => 'EUR',
                            'price' => 123.45,
                        ])
                        ->count(1)
                    )
                    ->has(Export::factory()
                        ->xml()
                        ->mappings([
                            'ean' => 'ean',
                            'price' => [
                                'value' => 'price',
                                'currency' => 'currency',
                            ],
                        ])
                    )
                    ->create();

        $job = new CompiledProductExportJob($compiler->exports->first(), $filename, 'export');

        $job->handle();

        $this->assertTrue(Storage::disk('export')->exists($filename));

        $expected =<<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<products><product><ean>12345</ean><price><value>123.45</value><currency>EUR</currency></price></product></products>

EOF;
        $this->assertEquals($expected, Storage::disk('export')->get($filename));

        Storage::disk('export')->delete($filename);
    }

    /**
     * @test
     * @return void
     */
    public function can_handle_product_attributes()
    {
        $filename = 'xml-export.xml';

        $fields = [
            'ean' => 'string',
            'price' => 'float',
            'currency' => 'string',
        ];

        $compiler = Compiler::factory()
                    ->fields($fields)
                    ->has(CompiledProduct::factory()
                        ->data([
                            'ean' => '12345',
                            'currency' => 'EUR',
                            'price' => 123.45,
                        ])
                        ->count(1)
                    )
                    ->has(Export::factory()
                        ->xml()
                        ->mappings([
                            'ean' => 'ean',
                            'price' => [
                                'name' => 'price',
                                'attributes' => [
                                    'value' => 'price',
                                    'currency' => 'currency',
                                ]
                            ],
                        ])
                    )
                    ->create();

        $job = new CompiledProductExportJob($compiler->exports->first(), $filename, 'export');

        $job->handle();

        $this->assertTrue(Storage::disk('export')->exists($filename));

        $expected =<<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<products><product><ean>12345</ean><price value="123.45" currency="EUR"/></product></products>

EOF;
        $this->assertEquals($expected, Storage::disk('export')->get($filename));

        Storage::disk('export')->delete($filename);
    }
}
