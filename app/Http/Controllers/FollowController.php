<?php

namespace App\Http\Controllers;

use Cache;
use Redis;
use DB;
use Auth;
use App\User;
use Illuminate\Http\Request;

use App\Http\Requests;

class FollowController extends Controller
{

	/**
     * Create a new authentication controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('auth', ['except' => 'auth\login']);
    }

    public function add($follow_uid)
    {
         $user = user();
         $uid = $user->id;

         if (Cache::get(USER_FOLLOWS_COUNT . $uid) >= MAX_FOLLOWS_COUNT) {
            return response()->json('您已关注' . MAX_FOLLOWS_COUNT . '人，不可再关注他人');
         }

         $follows_table = getFollowsTable($uid);
         $follows_me_table = getFollowsMeTable($follow_uid);

         DB::beginTransaction();
         try {
         	DB::connection('follows')->table($follows_table)->insert([
         		'uid' => $uid,
         		'follow_uid' => $follow_uid,
         		]);
         	DB::connection('follows')->table($follows_me_table)->insert([
         		'uid' => $uid,
         		'follow_uid' => $follow_uid,
         		]);
            DB::commit();
         } catch (Exception $e) {
         	DB::rollback();
         	return response()->json($e);
         }

         Cache::increment(USER_FOLLOWS_COUNT . $uid);
         Cache::increment(USER_FOLLOWS_ME_COUNT . $follow_uid);
         Redis::sadd(USER_FOLLOWS_SET . $uid, $follow_uid);
         Redis::sadd(USER_FOLLOWS_ME_SET . $follow_uid, $uid);

         return response()->json($uid . ' follow ' . $follow_uid . ' success');

    }

    public function delete($follow_uid)
    {
    	$user = user();
        $uid = $user->id;

        $follows_table = getFollowsTable($uid);
        $follows_me_table = getFollowsMeTable($follow_uid);

        if (!Redis::sismember(USER_FOLLOWS_SET . $uid, $follow_uid)) {
            if (!DB::connection('follows')->table($follows_table)->where('uid', $uid)->where('follow_uid', $follow_uid)->count()) {
                return response()->json($uid . ' did not follow ' . $follow_uid);
            }
        }

        DB::beginTransaction();
         try {
         	DB::connection('follows')->table($follows_table)->where('uid', $uid)->where('follow_uid', $follow_uid)->delete();
         	DB::connection('follows')->table($follows_me_table)->where('uid', $uid)->where('follow_uid', $follow_uid)->delete();
            DB::commit();
        } catch (Exception $e) {
         	DB::rollback();
         	return response()->json($e);
        }

        Cache::decrement(USER_FOLLOWS_COUNT . $uid);
        Cache::decrement(USER_FOLLOWS_ME_COUNT . $follow_uid);
        Redis::srem(USER_FOLLOWS_SET . $uid, $follow_uid);
        Redis::srem(USER_FOLLOWS_ME_SET . $follow_uid, $uid);

         return response()->json($uid . ' unfollow ' . $follow_uid . ' success');

    }

}
