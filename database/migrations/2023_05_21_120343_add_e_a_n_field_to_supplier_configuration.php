<?php

use App\Models\Supplier;
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
        Schema::table('suppliers', function (Blueprint $table) {
            $suppliers = Supplier::all();

            foreach($suppliers as $supplier) {
                if ($supplier->config('ean_path') != '')
                    continue;

                $supplier->configSet('ean_path', 'ean');
                $supplier->save();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('suppliers', function (Blueprint $table) {
            // The config option can linger no harm there.
        });
    }
};
