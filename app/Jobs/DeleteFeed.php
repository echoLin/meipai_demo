<?php

namespace App\Jobs;

use DB;
use Cache;
use App\Feed;
use App\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Redis as Redis;

class DeleteFeed extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    protected $uid;
    protected $feed_id;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($uid, $feed_id)
    {
        $this->uid = $uid;
        $this->feed_id = $feed_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $ym = substr($this->feed_id, 0, 4);
        $feeds_table = Feed::getFeedsTable($ym);
        $feeds_index_table = getFeedsIndexTable($this->uid);

        DB::beginTransaction();
        try {
            DB::connection('feeds')->table($feeds_index_table)->where('uid', $this->uid)->where('feed_id', $this->feed_id)->update([
                'status' => STATUS_DELETED
                ]);
            DB::connection('feeds')->table($feeds_table)->where('uid', $this->uid)->where('id', $this->feed_id)->update([
                'status' => STATUS_DELETED
                ]);
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return;
        }
        Cache::decrement(USER_FEEDS_COUNT . $this->uid);
        Redis::hdel(FEED_LIKES_COUNT, $this->feed_id);//删除点赞数
        Redis::del(FEED_LIKES_SET . $this->feed_id);//删除点赞集合
        Redis::hdel(FEED_LIST, $this->feed_id);

    }
}
