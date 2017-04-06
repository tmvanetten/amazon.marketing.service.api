<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOldAndNewBid extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('strategy_history', function (Blueprint $table) {
            $table->decimal('new_bid', 13, 2);
            $table->decimal('old_bid', 13, 2);
            $table->dropColumn('message');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('strategy_history', function (Blueprint $table) {
            //
        });
    }
}
