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
    protected $table = 'feeds_1609';

    public function __call($method, $parameters){
        if ($method == 'findOrFail') {
            $this->setTable(self::getFeedsTable(substr($parameters[0], 0, 4)));
        }
        parent::__call($method, $parameters);
    }

    public static function getFeedsId($uid, $ym = false) {
        $feeds_id = DB::connection('feeds')->table('feeds_id_'.sprintf("%03d", $uid%32))->insertGetId([]);
        return ($ym ? $ym : substr(date('Ym'),2,4)) . sprintf("%010d", $uid) . sprintf("%05d", $feeds_id);
    }

    public static function getFeedsTable($ym = false)
    {
        if ($ym) {
            return 'feeds_' . $ym;
        } else {
            return 'feeds_' . substr(date('Ym'),2,4);
        }
    }

    public static function getFeedLikesCount($feed_id) {
    	if (!$count = Redis::hget(FEED_LIKES_COUNT, $feed_id)) {
    		$count = DB::connection('likes')->table(getLikesFeedTable($feed_id))->where('feed_id', $feed_id)->count();
    		Redis::hset(FEED_LIKES_COUNT, $feed_id, $count);
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

    public static function loadFeeds ($uids, $max_id, $min_id, $start_time, $end_time)
    {
    	if ($max_id < $min_id)
    		return array();

        if (!$start_time) {
            $start_time = date('Y-m-d H:i:s');
        }

        if ($max_id != $min_id) {
            //3.时间与id进行对比
        	$max_ym = substr(date('Ym', strtotime($start_time)),2);
    	    $min_ym = substr($min_id, 0, 4);
            if ($end_time) {
                $end_ym = substr(date('Ym', strtotime($end_time)),2);
                $min_ym = $min_ym < $end_ym ? $end_ym : $min_ym;
            } else {
                $end_time = '2016-01-01 00:00:00';
            }
        	$feeds_table = self::getFeedsTable($max_ym);
        	$list = DB::connection('feeds')->table($feeds_table)->where('created_at', '<=', $start_time)->where('created_at', '>=', $end_time)->where('id', '>=', $min_id)->where('id','<=', $max_id)->where('status', STATUS_CHECKED)->whereIn('uid', $uids)->take(MAX_FEED_COUNT)->orderBy('created_at','desc')->get();
        	while (count($list) < MAX_FEED_COUNT) {
        		//1.查找上个月的数据
        		if ($max_ym == intval(substr($max_ym, 0, 2) . '01')) {
        			$max_ym = (intval(substr($max_ym, 0, 2)) - 1) * 1000 + 12;
        		} else {
        			$max_ym -= 1;
        		}
        		if($max_ym < $min_ym)
        			break;
        		$feeds_table = self::getFeedsTable($max_ym);
        		$list = array_merge($list, DB::connection('feeds')->table($feeds_table)->where('created_at', '<=', $start_time)->where('created_at', '>=', $end_time)->where('id', '>=', $min_id)->where('id','<=', $max_id)->where('status', STATUS_CHECKED)->whereIn('uid', $uids)->take(MAX_FEED_COUNT-count($list))->orderBy('created_at','desc')->get());
        	}
        } else {
            $ym = substr($max_id, 0,4);
            $feeds_table = self::getFeedsTable($ym);
            $list = DB::connection('feeds')->table($feeds_table)->where('id', $max_id)->get();
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
