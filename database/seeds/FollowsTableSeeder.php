<?php

use Illuminate\Database\Seeder;

class FollowsTableSeeder extends Seeder
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
    		// $uid = rand(1,2000);
    		// $follow_uid = rand(1,2000);
    		// while ($uid == $follow_uid) {
    		// 	$follow_uid = rand(1,2000);
    		// }
            $uid = rand(1,2000);
            $follow_uid = 1024;
            while ($uid == $follow_uid) {
                $uid = rand(1,2000);
            } 
    		$follows = 'follows_'.sprintf('%04d', $uid%1024);
    		$follows_me = 'follows_me_'.sprintf('%04d', $follow_uid%1024);
    		if(DB::connection('follows')->table($follows)->where('uid', '=', $uid)->where('follow_uid', '=', $follow_uid)->count()){
    			continue;
    		}
	        DB::connection('follows')->table($follows)->insert([
	        	'uid' => $uid,
	        	'follow_uid' => $follow_uid,
	        	]);
	        DB::connection('follows')->table($follows_me)->insert([
	        	'uid' => $uid,
	        	'follow_uid' => $follow_uid,
	        	]);
	        $i++;
	    }
    }
}
