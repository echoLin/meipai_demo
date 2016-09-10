<?php

use App\User;
use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
    	$i = 0;
    	while ($i<1000) {
            $user = new User;
            $user->name = str_random(10);
            $user->email = str_random(10).'@gmail.com';
            $user->password = bcrypt('secret');
	        $user->save();
	        $i++;
	    }
    }
}
