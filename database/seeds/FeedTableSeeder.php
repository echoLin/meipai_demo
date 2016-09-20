<?php

use App\Feed;
use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Redis as Redis;

class FeedTableSeeder extends Seeder
{

	protected $feed_id;
    protected $content;
    protected $user;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
    	$i = 0;
    	while ($i<5000) {
	    	$this->user = user();
	    	$ym = rand(1601, 1609);
	        $feeds_table = Feed::getFeedsTable($ym);
	        $feeds_index_table = getFeedsIndexTable($this->user->id);
	        $time = '2016-'.substr($ym, 2, 2).'-'.sprintf("%02d", rand(1,30)).' '.sprintf("%02d", rand(0,23)). ':' .sprintf("%02d", rand(0,59)). ':' .sprintf("%02d", rand(0,59));
	        $this->feed_id = Feed::getFeedsId($this->user->id, $ym);
	        $this->content = str_random(10);


		    DB::beginTransaction();
	        try {
	            //1.insert到feeds_xxxx
	            DB::connection('feeds')->table($feeds_table)->insert([
	                'id' => $this->feed_id,
	                'uid' => $this->user->id,
	                'content' => $this->content,
	                'status' => STATUS_CHECKED,
	                'created_at' => $time,
	                'updated_at' => $time,
	                ]);

	            //2.insert到feeds_index_xxx
	            DB::connection('feeds')->table($feeds_index_table)->insert([
	                'feed_id' => $this->feed_id,
	                'uid' => $this->user->id,
	                'status' => STATUS_CHECKED,
	                'created_at' => $time,
	                'updated_at' => $time,
	                ]);


	            //3.udpate user表的最大和最小feed_ids
	            if (!$this->user->min_feed_id) {
	                $this->user->min_feed_id = $this->feed_id;
	            }
	            if ($this->user->max_feed_id < $this->feed_id) {
	                $this->user->max_feed_id = $this->feed_id;
	            }
	            $this->user->save();

	            //4.提交
	            DB::commit();
	        } catch (Exception $e) {
	            DB::rollback();
	            var_dump('error');
	            return;
	        }


	        if ($this->user->max_feed_id == $this->user->min_feed_id) {
	            Redis::hset(USER_FEEDS_MIN_ID, $this->user->id, $this->feed_id);
	        }
	        if ($this->user->max_feed_id == $this->feed_id) {
	            Redis::hset(USER_FEEDS_MAX_ID, $this->user->id, $this->feed_id);
	        }
	        Redis::hset(USER_INFO, $this->user->id,  serialize($this->user));
	        Redis::hset(FEED_LIKES_COUNT, $this->feed_id, 0);//初始点赞数
	        Cache::increment(USER_FEEDS_COUNT . $this->user->id);
	        if ($this->user->getFollowsMeCount() >= FEED_CACHE_MIN_FOLLOWS_ME_COUNT) {//粉丝多的用户缓存动态内容
	            $feed = DB::connection('feeds')->table($feeds_table)->where('id', $this->feed_id)->first();
	            Redis::hset(FEED_LIST, $feed->id, serialize($feed));
	        }
	        $i++;
	    }
    }
}
