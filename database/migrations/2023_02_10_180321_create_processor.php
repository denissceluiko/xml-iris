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
        Schema::create('processors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('compiler_id');
            $table->unsignedBigInteger('supplier_id');
            $table->json('mappings')->nullable();
            $table->json('transformations')->nullable();
            $table->timestamps();

            $table->unique(['compiler_id', 'supplier_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropifExists('processors');
    }
};
