<?php

namespace App\Jobs;

use DB;
use App\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Redis as Redis;

class DeleteFollow extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    protected $uid;
    protected $follow_uid;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($uid, $follow_uid)
    {
        $this->uid = $uid;
        $this->follow_uid = $follow_uid;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $follows_table = getFollowsTable($this->uid);
        $follows_me_table = getFollowsMeTable($this->follow_uid);

        DB::beginTransaction();
         try {
            DB::connection('follows')->table($follows_table)->where('uid', $this->uid)->where('follow_uid', $this->follow_uid)->delete();
            DB::connection('follows')->table($follows_me_table)->where('uid', $this->uid)->where('follow_uid', $this->follow_uid)->delete();
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
        }

    }
}
