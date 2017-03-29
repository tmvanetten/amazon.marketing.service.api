<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAdgroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('adgroups', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->unsignedBigInteger('campaign_id');
            $table->decimal('default_bid', 13, 2);
            $table->string('state');
            $table->timestamps();
            $table->foreign('campaign_id')->references('id')->on('campaigns');

            $table->primary('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('adgroups');
    }
}
