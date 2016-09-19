<?php

use Illuminate\Support\Facades\Redis as Redis;
use App\User;

function user($uid = false)
{
	if (!$uid) {
		// if ($user = Auth::user())
  //   		return $user;
    	$uid = rand(1,5000);
	}
	//Redis::del('USER_INFO');
	if (!$user = unserialize(Redis::hget(USER_INFO, $uid))) {
		$user = User::find($uid);
		Redis::hset(USER_INFO, $uid,  serialize($user));
	}
	return $user;
}

function getFollowsTable($uid)
{
	return 'follows_' . sprintf("%04d", $uid%1024);
}

function getFollowsMeTable($follow_uid)
{
	return 'follows_me_' . sprintf("%04d", $follow_uid%1024);
}

function getLikesTable($uid)
{
	return 'likes_' . sprintf("%04d", $uid%1024);
}

function getLikesFeedTable($feed_id)
{
	return 'likes_feed_' . sprintf("%04d", $feed_id%1024);
}

function getFeedsIndexTable($uid)
{
	return 'feeds_index_' . sprintf("%03d", $uid%128);
}






