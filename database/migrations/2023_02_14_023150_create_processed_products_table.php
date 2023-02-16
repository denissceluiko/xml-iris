<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('processed_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('processor_id');
            $table->json('extracted_data')->nullable();
            $table->json('transformed_data')->nullable();
            // 0 - not stale; 1 - transformed data is stale; 2 - extracted data is stale
            $table->tinyInteger('stale_level')->default(2);
            $table->timestamps();

            $table->unique(['product_id', 'processor_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('processed_products');
    }
};
