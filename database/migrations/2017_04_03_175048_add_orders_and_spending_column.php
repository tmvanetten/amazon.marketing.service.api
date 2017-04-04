<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOrdersAndSpendingColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('search_terms_report', function (Blueprint $table) {
            $table->unsignedInteger('one_week_order');
            $table->decimal('one_week_sales', 13, 2);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('search_terms_report', function (Blueprint $table) {
            //
        });
    }
}
