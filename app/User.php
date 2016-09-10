<?php

namespace App;

use DB;
use Redis;
use Cache;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function getFollowsList() {
        if (!$follow_uids = Redis::smembers(USER_FOLLOWS_SET . $this->id)) {
            $follow_uids = DB::connection('follows')->table(getFollowsTable($this->id))->where('uid', $this->id)->lists('follow_uid');
            Redis::sadd(USER_FOLLOWS_SET . $this->id, $follow_uids);
        }
        return $follow_uids;
    }

    public function getFeedsCount () {
        if (!$count = Cache::get(USER_FEEDS_COUNT . $this->id)) {
            $count = DB::connection('feeds')->table(getFeedsIndexTable($this->id))->where('uid', $this->id)->where('status',STATUS_CHECKED)->count();
            Cache::forever(USER_FEEDS_COUNT . $this->id, $count);
        }
        return $count;
    }

    public function getFeedsRealCount () {
        if (!$count = Cache::get(USER_FEEDS_REAL_COUNT . $this->id)) {
            $count = DB::connection('feeds')->table(getFeedsIndexTable($this->id))->where('uid', $this->id)->count();
            Cache::forever(USER_FEEDS_REAL_COUNT . $this->id, $count);
        }
        return $count;
    }

    public function getFollowsMeCount () {
        if (!$count = Cache::get(USER_FOLLOWS_ME_COUNT . $this->id) ) {
            if (!$count = Redis::scard(USER_FOLLOWS_ME_SET . $this->id)) {
              $count = DB::connection('follows')->table(getFollowsMeTable($this->uid))->where('follow_uid', $this->id)->count();
            }
            Cache::forever(USER_FOLLOWS_ME_COUNT . $this->id, $count);
        }
        return $count;
    }

    public static function getMultiUserInfo ($uids) {
        $uids = array_unique($uids);
        $users = Redis::hmget(USER_INFO, $uids);
        if (array_filter($users) != count($uids)) {
            foreach ($users as $k => $v) {
                if (!$v) {
                    $else[] = $uids[$k];

                } else {
                    $v = unserialize($v);
                    $users[$v->id] = $v;
                }
                unset($users[$k]);
            }
            foreach ($else as $uid) {
                $users[$uid] = user($uid);
            }
        }
        return $users;
    }

}
