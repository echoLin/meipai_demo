<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFeedsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        for($y=16; $y<17; $y++){
            for($m=1; $m<13; $m++){
                Schema::connection('feeds')->create('feeds_' . $y . sprintf("%02d", $m), function (Blueprint $table) {
                        $table->bigInteger('id')->primary();//ymd+uid+uniqid
                        $table->integer('uid');
                        $table->tinyInteger('status');
                        $table->string('content');
                        $table->timestamps();
                        $table->index('uid');
                        $table->index('created_at');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        for($y=16; $y<18; $y++){
            for($m=1; $m<13; $m++){
                Schema::connection('feeds')->drop('feeds_'  . $y . sprintf("%02d", $m));
            }
        }
    }
}
