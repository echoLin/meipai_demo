<?php

namespace App\Http\Controllers;

use Cache;
use DB;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis as Redis;
use App\Http\Requests;

class UserController extends Controller
{
    public function index($uid = false)
    {
        $user = user($uid);
        $uid = $user->id;

        if (!$user = unserialize(Redis::hget('USER_INFO', $uid))) {
            $user = User::find($uid);
            Redis::hset('USER_INFO', $uid,  serialize($user));
        }

        return response()->json(unserialize(Redis::hget('USER_INFO', $uid)));


        //return response()->json($user);

    	if (!$user['follows'] = Cache::get(USER_FOLLOWS_COUNT . $uid)) {
            if (!$user['follows'] = Redis::scard(USER_FOLLOWS_SET . $uid)) {
    		  $user['follows'] = DB::connection('follows')->table(getFollowsTable($uid))->where('uid', $uid)->count();
            }
    		Cache::forever(USER_FOLLOWS_COUNT . $uid, $user['follows']);
    	}

    	if (!$user['follows_me'] = Cache::get(USER_FOLLOWS_ME_COUNT . $uid)) {
            if (!$user['follows_me'] = Redis::scard(USER_FOLLOWS_ME_SET . $uid)) {
    		  $user['follows_me'] = DB::connection('follows')->table(getFollowsMeTable($uid))->where('follow_uid', $uid)->count();
            }
    		Cache::forever(USER_FOLLOWS_ME_COUNT . $uid, $user['follows_me']);
    	}

    	if (!$user['feeds'] = Cache::get(USER_FEEDS_COUNT . $uid)) {
    		$user['feeds'] = DB::connection('feeds')->table(getFeedsIndexTable($uid))->where('uid', $uid)->where('status',STATUS_CHECKED)->count();
    		Cache::forever(USER_FEEDS_COUNT . $uid, $user['feeds']);
    	}

        return response()->json($user);
    }
}
