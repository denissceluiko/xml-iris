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
        Schema::create('compilier_supplier', function (Blueprint $table) {
            $table->unsignedBigInteger('compilier_id');
            $table->unsignedBigInteger('supplier_id');

            $table->unique(['compilier_id', 'supplier_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropifExists('compilier_supplier');
    }
};
