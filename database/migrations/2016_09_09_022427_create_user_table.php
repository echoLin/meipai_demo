<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('meipai')->create('users', function (Blueprint $table) {
                $table->increments('id');
                $table->string('email')->unique();
                $table->string('name');
                $table->string('password', 60);
                $table->bigInteger('max_feed_id');
                $table->bigInteger('min_feed_id');
                $table->rememberToken();
                $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('meipai')->drop('users');
    }
}
