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
        Schema::create('compiled_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('processed_product_id')->nullable();
            $table->unsignedBigInteger('compiler_id');
            $table->string('ean')->index();
            $table->json('data')->nullable();

            // 0 - not stale; 1 - stale
            $table->tinyInteger('stale_level')->default(1);
            $table->timestamps();

            $table->unique(['compiler_id', 'ean']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('compiled_products');
    }
};
