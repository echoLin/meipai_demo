<?php

use App\User;

function user($uid = false)
{
	if (!$uid) {
		if ($user = Auth::user())
    		return $user;
    	$uid = rand(1,2000);
	}
	return 'hahah';
	$user = new User;
	$user->id = 1023;
	$user->name = 'hahah';
	return $user;
	return DB::connection('meipai')->table('users')->where('id', $uid)->first();
	//Redis::del('USER_INFO');
	if (!$user = unserialize(Redis::hget('USER_INFO', $uid))) {
		$user = User::find($uid);
		Redis::hset('USER_INFO', $uid,  serialize($user));
	}
	return $user;
}

function returnErrorJson($error_detail = '非法请求', $errno = '10101', $error = '系统错误，请重试')
{
	return json_encode(array('errno' => $errno, 'error' => $error, 'error_detail' => $error_detail), JSON_UNESCAPED_UNICODE);
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

function getFeedsTable($ym = false)
{
	if ($ym) {
		return 'feeds_' . $ym;
	} else {
		return 'feeds_' . substr(date('Ym'),2,4);
	}
}

function getFeedsIndexTable($uid)
{
	return 'feeds_index_' . sprintf("%03d", $uid%128);
}

function getFeedsId($user)
{
	$feeds_real_count = $user->getFeedsRealCount();
	return substr(date('Ym'),2,4) . sprintf("%010d", $user->id) . sprintf("%05d", $feeds_real_count+1);
}