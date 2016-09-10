<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFeedsIndexTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        for($i=0; $i<128; $i++) {
            Schema::connection('feeds')->create('feeds_index_' . sprintf("%03d", $i), function (Blueprint $table) {
                $table->bigInteger('feed_id');//ymd+uid+uniqid
                $table->integer('uid');
                $table->tinyInteger('status');
                $table->timestamps();
                $table->primary(['feed_id', 'uid']);
                $table->index('uid');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
