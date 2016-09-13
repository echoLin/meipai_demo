<?php

namespace App\Jobs;

use DB;
use Log;
use App\User;
use App\Feed;
use App\Feedsindex;
use App\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Redis as Redis;

class PublishFeed extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    protected $feed;
    protected $user;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Feed $feed, User $user)
    {
        $this->feed = $feed;
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $feeds_table = getFeedsTable();
        $feeds_index_table = getFeedsIndexTable($this->feed->uid);
        //Log::info('in queue');
        DB::beginTransaction();
        try {
            //1.insert到feeds_xxxx
            $this->feed->setTable($feeds_table);
            $this->feed->status = STATUS_CHECKED;
            $this->feed->save();

            //2.insert到feeds_index_xxx
            $feeds_index = new Feedsindex;
            $feeds_index->setTable($feeds_index_table);
            $feeds_index->uid = $this->feed->uid;
            $feeds_index->feed_id = $this->feed->id;
            $feeds_index->status = STATUS_CHECKED;
            $feeds_index->save();

            //3.udpate user表的最大和最小feed_id
            if (intval(substr($this->feed->id, 14,5)) == 1) {
                $this->user->min_feed_id = $this->feed->id;
            } 
            $this->user->max_feed_id = $this->feed->id;
            $this->user->save();

            //4.提交
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return false;
        }
        if ($this->user->getFollowsMeCount() >= FEED_CACHE_MIN_FOLLOWS_ME_COUNT) {//粉丝多的用户缓存动态内容
            Redis::hset(FEED_LIST, $this->feed->id, serialize($this->feed));
        }
    }
}
