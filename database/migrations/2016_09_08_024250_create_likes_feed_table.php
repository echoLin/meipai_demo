<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLikesFeedTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        for($i=0; $i<1024; $i++){
            Schema::connection('likes')->create('likes_feed_'.sprintf('%04d', $i), function (Blueprint $table) {
                $table->increments('id');
                $table->integer('uid');
                $table->bigInteger('feed_id');
                $table->timestamps('created_at');
                $table->unique(['uid', 'feed_id']);
                $table->index('feed_id');
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
        for($i=0; $i<1024; $i++){
            Schema::connection('likes')->drop('likes_feed_'.sprintf('%04d', $i));
        }
    }
}
