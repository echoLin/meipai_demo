<?php
namespace App;

use Cache;
use DB;
use App\User;
use Illuminate\Support\Facades\Redis as Redis;
use Illuminate\Database\Eloquent\Model;


class Feed extends Model
{
    protected $connection = 'feeds';

    public function __call($method, $parameters){
        if ($method == 'findOrFail') {
            $this->setTable(getFeedsTable(substr($parameters[0], 0, 4)));
        }
        parent::__call($method, $parameters);
    }

    public function getFeedLikesCount() {
    	if (!$count = Redis::hget(FEED_LIKES_COUNT, $this->id)) {
    		$count = DB::connection('likes')->table(getLikesFeedTable($this->id))->where('feed_id', $this->id)->count();
    		Redis::hset(FEED_LIKES_COUNT, $this->id, $count);
    	}
    	return $count;
    }

    public static function getFeedMinIdByUids ($uids) 
    {
    	$min_ids = Redis::hmget(USER_FEEDS_MIN_ID, $uids);
		if (count(array_filter($min_ids)) != count($uids)) {
			$else = array();
			foreach ($min_ids as $k => $v) {
				if (!$v)
					$else[] = $uids[$k];
			}
			$list = DB::connection('meipai')->table('users')->whereIn('id', $else)->lists('min_feed_id','id');
			$min_ids = array_filter(array_merge($min_ids, array_values($list)));
			Redis::hmset(USER_FEEDS_MIN_ID, $list);
		}
		if (!$min_ids) {
			return false;
		}
		sort($min_ids);
		return $min_ids[0];
    }

    public static function getFeedMaxIdByUids ($uids) 
    {
    	$max_ids = Redis::hmget(USER_FEEDS_MAX_ID, $uids);
		if (count(array_filter($max_ids)) != count($uids)) {
			$else = array();
			foreach ($max_ids as $k => $v) {
				if (!$v)
					$else[] = $uids[$k];
			}
			$list = DB::connection('meipai')->table('users')->whereIn('id', $else)->lists('max_feed_id','id');
			$max_ids = array_filter(array_merge($max_ids, array_values($list)));
			Redis::hmset(USER_FEEDS_MAX_ID, $list);
		}
		if (!$max_ids) {
			return false;
		}
		rsort($max_ids);
		return $max_ids[0];
    }

    public static function loadFeeds ($uids, $max_id, $min_id)
    {
    	if ($max_id == $min_id)
    		return array();
    	$max_ym = substr($max_id, 0, 4);
	    $min_ym = substr($min_id, 0, 4);
    	$feeds_table = getFeedsTable($max_ym);
    	$list = DB::connection('feeds')->table($feeds_table)->where('id', '>=', $min_id)->where('id','<', $max_id)->where('status', STATUS_CHECKED)->whereIn('uid', $uids)->take(MAX_FEED_COUNT)->orderBy('created_at','desc')->get();
    	while (count($list) < MAX_FEED_COUNT) {
    		//1.查找上个月的数据
    		if ($max_ym == intval(substr($max_ym, 0, 2) . '01')) {
    			$max_ym = (intval(substr($max_ym, 0, 2)) - 1) * 1000 + 12;
    		} else {
    			$max_ym -= 1;
    		}
    		if($max_ym < $min_ym)
    			break;
    		$feeds_table = getFeedsTable($max_ym);
    		$list = array_merge($list, DB::connection('feeds')->table($feeds_table)->where('id', '>=', $min_id)->where('id','<', $max_id)->where('status', STATUS_CHECKED)->whereIn('uid', $uids)->take(MAX_FEED_COUNT-count($list))->orderBy('created_at','desc')->get());
    	}
    	if (!$list)
    		return array();
    	$ids = array();
    	$uids = array();
    	foreach ($list as $k => $v) {
    		$ids[] = $v->id;
    		$uids[] = $v->uid;
    	}

    	//获取动态点赞数
    	$likes = Redis::hmget(FEED_LIKES_COUNT, $ids);
    	$users = User::getMultiUserInfo($uids);
    	foreach ($list as $k => $v) {
    		$v->likes = $likes[$k];
    		$v->user = $users[$v->uid];
    		$list[$v->id] = $v;
    		unset($list[$k]);
    	}

    	return $list;
    }
}
