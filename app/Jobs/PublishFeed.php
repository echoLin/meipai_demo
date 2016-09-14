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

    protected $feed_id;
    protected $content;
    protected $user;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $user, $feed_id, $content)
    {
        $this->user = $user;
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
        $feeds_table = getFeedsTable();
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

            //3.udpate user表的最大和最小feed_id
            if (intval(substr($this->feed_id, 14,5)) == 1) {
                $this->user->min_feed_id = $this->feed_id;
            } 
            $this->user->max_feed_id = $this->feed_id;
            $this->user->save();

            //4.提交
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
        }
    }
}
