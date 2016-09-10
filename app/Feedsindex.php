<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class Feedsindex extends Model
{
    protected $connection = 'feeds';

    protected $table = '';

    public function setTable ($table)
    {
        $this->table = $table;
    }

    public static function getFeedIds ($uids) 
    {

    }
}
