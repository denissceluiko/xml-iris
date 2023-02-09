<?php

namespace Tests\Feature;

use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SupplierTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_can_pull_supplier()
    {
        $supplier = Supplier::factory()->create();

        $response = $this->get(route('supplier.show', $supplier));

        $response->assertStatus(200);
        $response->assertSee('5903396184178');
        $response->assertSee('[kategoria]');
    }

    public function test_can_save_products()
    {
        // $supplier = Supplier::factory()->create();

        // $supplier->pull();

        // $this->assertDatabaseCount('products', 4);
    }
}
