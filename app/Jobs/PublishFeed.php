<?php

namespace App\Jobs;

use DB;
use App\User;
use App\Feed;
use App\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Redis as Redis;

class PublishFeed extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    protected $feed_id;
    protected $content;
    protected $uid;
    protected $user;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($uid, $feed_id, $content)
    {
        $this->uid = $uid;
        $this->feed_id = $feed_id;
        $this->content = $content;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->user = user($this->uid);
        $feeds_table = Feed::getFeedsTable();
        $feeds_index_table = getFeedsIndexTable($this->user->id);
        $time = date('Y-m-d H:i:s');

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
    }
}
