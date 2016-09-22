<?php

namespace App\Http\Controllers;

use Cache;
use DB;
use App\User;
use App\Feed;
use App\Jobs\PublishFeed;
use App\Jobs\DeleteFeed;
use App\Jobs\PostLike;
use App\Jobs\DeleteLike;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis as Redis;
use App\Http\Requests;
use App\Http\Controllers\Controller;

class FeedController extends Controller
{
    //修改了发号器之后需要重写获取动态的逻辑，因为id与时间无关了。
    public function index ($start_time = 0, $end_time = 0, $uid = false)
    {	
        if (!$uid) {
    	    $user = user(3);
    	    $uid = $user->id;

    	    //1.获取用户的关注列表
    		$follow_uids = $user->getFollowsList();
    		if (!$follow_uids) {
    			return response()->json($uid  . ' did not follow anyone');
    		}
        } else {
            $follow_uids = array($uid);
        }
        //return response()->json($follow_uids);


		//2.获取关注用户中发布的最大最小动态ID
		$max_id = Feed::getFeedMaxIdByUids($follow_uids);
		$min_id = Feed::getFeedMinIdByUids($follow_uids);
		if (!$min_id) {
			return response()->json('Do not have any feeds');
		}
        
    	$feeds = Feed::loadFeeds($follow_uids, $max_id, $min_id, $start_time, $end_time);

    	return response()->json($feeds);

    }

    public function feed ($feed_id)
    {
    	if (!$feed =  unserialize(Redis::hget(FEED_LIST, $feed_id))) {
    		$feed = DB::connection('feeds')->table(Feed::getFeedsTable(substr($feed_id, 0, 4)))->where('id', $feed_id)->where('status', STATUS_CHECKED)->first();
    	    $feed->likes = Feed::getFeedLikesCount($feed->id);
        }
        $feed->user = user($feed->uid);
    	return response()->json($feed);
    }

    public function add (Request $request)
    {
    	$content = $request->input('content');

        $user = user();
        $uid = $user->id;

        $feed = new Feed;
        $feed->id = Feed::getFeedsId($user->id);
        $feed->uid = $uid;
        $feed->content = $content;

        $this->dispatch(new PublishFeed($user->id,$feed->id, $feed->content));
        return response()->json($feed);
    }

    public function delete ($feed_id)
    {
    	$uid = intval(substr($feed_id, 4,10));
    	$user = user($uid);//*
    	if ($uid != $user->id) {
    		return response()->json($feed_id . ' is not your feed');
    	}

        $this->dispatch(new DeleteFeed($uid, $feed_id));

        return response()->json('delete ' . $feed_id . ' success');
    }

    public function like ($feed_id)
    {
        $user = user();
        $uid = $user->id;
        if (Redis::sismember(FEED_LIKES_SET . $feed_id, $uid)) {
            return response()->json($uid . ' likes ' . $feed_id . ' already');
        }
        
        $this->incrFeedCache($user, $feed_id, 'likes', 1);
        $this->dispatch(new PostLike($uid, $feed_id));
        return response()->json($uid . ' likes ' . $feed_id .' success');
    }

    public function unlike ($feed_id)
    {
        $user = user();
        $uid = $user->id;
        if (!Redis::sismember(FEED_LIKES_SET . $feed_id, $uid)) {
            return response()->json($uid . ' did not likes ' . $feed_id);
        }

        $this->incrFeedCache($user, $feed_id, 'likes', -1);
        $this->dispatch(new DeleteLike($uid, $feed_id));

        return response()->json($uid . ' unlikes ' . $feed_id .' success');

    }

    private function incrFeedCache ($user, $feed, $type, $increment) 
    {
    	$uid = $user->id;
    	switch ($type) {
    		case 'likes':
    			$feed_id = $feed;
    			$feed = new Feed;
    			$feed->id = $feed_id;
    			Redis::hincrby(FEED_LIKES_COUNT, $feed_id, $increment);
		        if ($increment == 1) {//点赞
		        	Redis::sadd(FEED_LIKES_SET . $feed_id, $uid);
		        	if ($count=Redis::hget(FEED_LIKES_COUNT, $feed_id) >= FEED_CACHE_MIN_LIKES_COUNT) {
			        	if (!$feed =  unserialize(Redis::hget(FEED_LIST, $feed_id))) {
                            $feed = DB::connection('feeds')->table(Feed::getFeedsTable(substr($feed_id, 0, 4)))->where('id', $feed_id)->where('status', STATUS_CHECKED)->first();
                        }
                        $feed->likes = $count;
                        Redis::hset(FEED_LIST, $feed_id, serialize($feed));
			        }
		        } else {//取消赞
		        	Redis::srem(FEED_LIKES_SET . $feed_id, $uid);
		        }
    			break;
            default:
                break;
    	}
    }

    public function redis ($key = false) {
    	if (!$key) {
    		Redis::del(USER_INFO);
    		Redis::del(USER_FEEDS_MAX_ID);
    		Redis::del(USER_FEEDS_MIN_ID);
    		Redis::del(FEED_LIKES_COUNT);
    		Redis::del(FEED_LIKES_SET);
    		Redis::del(FEED_LIST);
    		Redis::del(USER_INFO);
    	} else {
    		Redis::del($key);
    	}
        return response()->json('ok');
    }

}
