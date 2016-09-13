<?php

namespace App\Http\Controllers;

use Cache;
use DB;
use App\User;
use App\Feed;
use App\Feedsindex;
use App\Jobs\PublishFeed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis as Redis;
use App\Http\Requests;
use App\Http\Controllers\Controller;

class FeedController extends Controller
{
    public function index ($max = 0, $min = 0)
    {	
	    $user = user();
	    $uid = $user->id;

	    //1.获取用户的关注列表
		$follow_uids = $user->getFollowsList();
		if (!$follow_uids) {
			return response()->json($uid  . '尚未关注任何人, 因此无可看动态');
		}

		//2.获取关注用户中发布的最大最小动态ID
		$max_id = Feed::getFeedMaxIdByUids($follow_uids);
		$min_id = Feed::getFeedMinIdByUids($follow_uids);
		if (!$min_id) {
			return response()->json('您关注的人没有发布过动态呢');
		}

    	//4.处理参照id
    	if ($max > 0 && $max < $max_id) {
    		$max_id = $max;
    	}
    	if ($min > 0 && $min > $min_id) {
    		$min_id = $min;
    	}

    	$feeds = Feed::loadFeeds($follow_uids, $max_id, $min_id);

    	return response()->json($feeds);

    }

    public function feed ($feed_id)
    {
    	if (!$feed =  unserialize(Redis::hget(FEED_LIST, $feed_id))) {
    		$feed = DB::connection('feeds')->table(getFeedsTable(substr($feed_id, 0, 4)))->where('id', $feed_id)->where('status', STATUS_CHECKED)->first();
    	}
    	$feed->likes = $feed->getFeedLikesCount();
    	$feed->user = user($feed->uid);
    	return response()->json($feed);
    }

    public function add (Request $request)
    {
    	$content = $request->input('content');

    	$user = user();
    	$uid = $user->id;

        // $feed = new Feed;
        // $feed->id = getFeedsId($user);
        // $feed->uid = $uid;
        // $feed->content = $content;
        // // Log::info('feed@add');
        // // return response()->json($feed);
        // // Log::info($feed->toArray());

        // $this->dispatch(new PublishFeed($feed, $user));
        


        $feeds_table = getFeedsTable();
        $feeds_index_table = getFeedsIndexTable($uid);
        $feed_id = getFeedsId($user);

        DB::beginTransaction();
        try {
            //1.insert到feeds_xxxx
            $feed = new Feed;
            $feed->setTable($feeds_table);
            $feed->id = $feed_id;
            $feed->uid = $uid;
            $feed->status = STATUS_CHECKED;
            $feed->content = $content;
            $feed->save();

            //2.insert到feeds_index_xxx
            $feeds_index = new Feedsindex;
            $feeds_index->setTable($feeds_index_table);
            $feeds_index->uid = $uid;
            $feeds_index->feed_id = $feed_id;
            $feeds_index->status = STATUS_CHECKED;
            $feeds_index->save();

            //3.udpate user表的最大和最小feed_id
            if (intval(substr($feed_id, 14,5)) == 1) {
                $user->min_feed_id = $feed_id;
            } 
            $user->max_feed_id = $feed_id;
            $user->save();

            //4.提交
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return response()->json($e);
        }

        $this->incrFeedCache($user, $feed, 'feeds', 1);

        return response()->json($feed);
    }

    public function delete ($feed_id)
    {
    	$uid = intval(substr($feed_id, 4,10));
    	$ym = substr($feed_id, 0, 4);
    	$user = user($uid);//*
    	if ($uid != $user->id) {
    		return response()->json($feed_id . ' is not your feed');
    	}

    	$feeds_table = getFeedsTable($ym);
        $feeds_index_table = getFeedsIndexTable($uid);

        DB::beginTransaction();
        try {
        	DB::connection('feeds')->table($feeds_index_table)->where('uid', $uid)->where('feed_id', $feed_id)->update([
        		'status' => STATUS_DELETED
        		]);
        	DB::connection('feeds')->table($feeds_table)->where('uid', $uid)->where('id', $feed_id)->update([
        		'status' => STATUS_DELETED
        		]);
        	DB::commit();
        } catch (Exception $e) {
        	DB::rollback();
        	return response()->json($e);
        }

        $this->incrFeedCache($user, $feed_id, 'feeds', -1);

        return response()->json('delete ' . $feed_id . ' success');
    }

    public function like ($feed_id)
    {
        $user = user();
        $uid = $user->id;
        if (!Redis::sismember(FEED_LIKES_SET . $feed_id, $uid)) {
            return response()->json($uid . 'likes ' . $feed_id . ' already');
        }
        $likes_table = getLikesTable($uid);
        $likes_feed_table = getLikesFeedTable($feed_id);

        DB::beginTransaction();
        try {
            DB::connection('likes')->table($likes_table)->insert([
                'uid' => $uid,
                'feed_id' => $feed_id,
                ]);
            DB::connection('likes')->table($likes_feed_table)->insert([
                'uid' => $uid,
                'feed_id' => $feed_id,
                ]);
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return response()->json($e);
        }
        
        $this->incrFeedCache($user, $feed_id, 'likes', 1);

        return response()->json($uid . ' likes ' . $feed_id .' success');
    }

    public function unlike ($feed_id)
    {
        $user = user();
        $uid = $user->id;
        if (Redis::sismember(FEED_LIKES_SET . $feed_id, $uid)) {
            return response()->json($uid . 'already likes ' . $feed_id);
        }

        $likes_table = getLikesTable($uid);
        $likes_feed_table = getLikesFeedTable($feed_id);

        DB::beginTransaction();
        try {
            DB::connection('likes')->table($likes_table)->where('uid', $uid)->where('feed_id', $feed_id)->delete();
            DB::connection('likes')->table($likes_feed_table)->where('uid', $uid)->where('feed_id', $feed_id)->delete();
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return response()->json($e);
        }

        $this->incrFeedCache($user, $feed_id, 'likes', -1);

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
    			$count = $feed->getFeedLikesCount();
    			Redis::hincrby(FEED_LIKES_COUNT, $feed_id, $increment);
		        if ($increment == 1) {//点赞
		        	Redis::sadd(FEED_LIKES_SET . $feed_id, $uid);
		        	if (Redis::hget(FEED_LIKES_COUNT, $feed_id) >= FFED_CACHE_MIN_LIKES_COUNT) {
			        	Redis::hsetnx(FEED_LIST, $feed_id, serialize($feed));
			        }
		        } else {//取消赞
		        	Redis::srem(FEED_LIKES_SET . $feed_id, $uid);
		        }
    			break;
    		case 'feeds':
    			if ($increment == 1) {//添加动态
    			    $feed_id = $feed->id;
    			    //echo $feed;die();
    				Redis::hset(FEED_LIKES_COUNT, $feed_id, 0);//初始点赞数
    				if (intval(substr($feed_id, 14,5)) == 1) {//第一条动态
    					Redis::hset(USER_FEEDS_MIN_ID, $uid, $feed_id);
    				}
    				Redis::hset(USER_FEEDS_MAX_ID, $uid, $feed_id);
    				Cache::increment(USER_FEEDS_COUNT . $uid);
    				Cache::increment(USER_FEEDS_REAL_COUNT . $uid);
                    if ($user->getFollowsMeCount() >= FEED_CACHE_MIN_FOLLOWS_ME_COUNT) {//粉丝多的用户缓存动态内容
                        Redis::hset(FEED_LIST, $this->feed->id, serialize($this->feed));
                    }
    			} else {//删除动态
    			    $feed_id = $feed;
    			    Cache::decrement(USER_FEEDS_COUNT);
    				Redis::hdel(FEED_LIKES_COUNT, $feed_id);//删除点赞数
    				Redis::del(FEED_LIKES_SET . $feed_id);//删除点赞集合
    				Redis::hdel(FEED_CONTENT_SET, $feed_id);
    			}
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
    }

}
