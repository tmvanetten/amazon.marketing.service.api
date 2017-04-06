<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDateRangeAndRunDays extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('strategy', function (Blueprint $table) {
            $table->unsignedInteger('recent_date_range')->default(1);
            $table->unsignedInteger('past_date_range')->default(1);
            $table->unsignedInteger('run_days')->default(1);
            $table->unsignedInteger('date_offset')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('strategy', function (Blueprint $table) {
            //
        });
    }
}
