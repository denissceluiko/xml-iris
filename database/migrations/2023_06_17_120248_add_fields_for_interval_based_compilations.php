<?php

use App\Models\Compiler;
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
        Schema::table('compilers', function (Blueprint $table) {
            $table->integer('interval')->default(0)->nullable();
            $table->timestamp('last_compiled_at')->nullable();
        });

        Compiler::where('interval', 0)->update([
            'interval' => 3600,
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('compilers', function (Blueprint $table) {
            $table->dropColumn(['last_compiled_at', 'interval']);
        });
    }
};
