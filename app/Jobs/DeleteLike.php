<?php

namespace App\Jobs;

use DB;
use App\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class DeleteLike extends Job implements ShouldQueue
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
        $likes_table = getLikesTable($this->uid);
        $likes_feed_table = getLikesFeedTable($this->feed_id);

        DB::beginTransaction();
        try {
            DB::connection('likes')->table($likes_table)->where('uid', $this->uid)->where('feed_id', $this->feed_id)->delete();
            DB::connection('likes')->table($likes_feed_table)->where('uid', $this->uid)->where('feed_id', $this->feed_id)->delete();
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
        }
    }
}
