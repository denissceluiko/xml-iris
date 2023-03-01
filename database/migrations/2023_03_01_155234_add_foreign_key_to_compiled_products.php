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
        Schema::disableForeignKeyConstraints();

        Schema::table('compiled_products', function (Blueprint $table) {
            $table->foreign('processed_product_id')
                    ->references('id')
                    ->on('processed_products')
                    ->cascadeOnDelete();
        });
        
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('compiled_products', function (Blueprint $table) {
            $table->dropForeign(['processed_product_id']);
        });
    }
};
