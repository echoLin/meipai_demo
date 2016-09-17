<?php

use DB;
use Illuminate\Database\Seeder;

class FeedTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
    	$i = 0;
    	while ($i<5000) {
	    	$user = user();
	    	$ym = rand(1601, 1612);
	        $feeds_table = getFeedsTable($ym);
	        $feeds_index_table = getFeedsIndexTable($this->user->id);
	        $time = date('Y-m-d H:i:s', rand(strtotime($ym.'01000000'), strtotime($ym, '30115959')));
	        $feed_id = getFeedsId($user);
	        $feed_content = str_random(10);

	        DB::beginTransaction();
	        try {
	            //1.insert到feeds_xxxx
	            DB::connection('feeds')->table($feeds_table)->insert([
	                'id' => $feed_id,
	                'uid' => $user->id,
	                'content' => $feed_content,
	                'status' => STATUS_CHECKED,
	                'created_at' => $time,
	                'updated_at' => $time,
	                ]);

	            //2.insert到feeds_index_xxx
	            DB::connection('feeds')->table($feeds_index_table)->insert([
	                'feed_id' => $feed_id,
	                'uid' => $user->id,
	                'status' => STATUS_CHECKED,
	                'created_at' => $time,
	                'updated_at' => $time,
	                ]);

	            //3.udpate user表的最大和最小feed_id
	            if (intval(substr($feed_id, 14,5)) == 1) {
	                $this->user->min_feed_id = $feed_id;
	            } 
	            $this->user->max_feed_id = $feed_id;
	            $this->user->save();

	            //4.提交
	            DB::commit();
	        } catch (Exception $e) {
	            DB::rollback();
        	}
        	$i++;
        }
    }
}
