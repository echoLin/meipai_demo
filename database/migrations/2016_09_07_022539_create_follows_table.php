<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFollowsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        for($i=0; $i<1024; $i++){
            Schema::connection('follows')->create('follows_'.sprintf('%04d', $i), function (Blueprint $table) {
                $table->increments('id');
                $table->integer('uid');
                $table->integer('follow_uid');
                $table->timestamps('created_at');
                $table->unique(['uid', 'follow_uid']);
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
            Schema::connection('follows')->drop('follows_'.sprintf('%04d', $i));
        }
    }
}
