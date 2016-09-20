<?php

//最大可关注人数
define('MAX_FOLLOWS_COUNT', 200);
//最大动态页数
define('MAX_FEED_PAGE', 10);
//每页动态数
define('MAX_FEED_COUNT',20);


//用户相关
//1.缓存
define('USER_INFO', '1:1');
define('USER_FOLLOWS_COUNT', '1:2:1:');
define('USER_FOLLOWS_SET', '1:2:2:');
define('USER_FOLLOWS_ME_COUNT', '1:3:1:');
define('USER_FOLLOWS_ME_SET', '1:3:2:');
define('USER_FEEDS_COUNT', '1:4:1:');
define('USER_FEEDS_REAL_COUNT', '1:4:2:');//所有状态下的动态总数
define('USER_FEEDS_MAX_ID', '1:4:2');//用户所有动态中的最大ID
define('USER_FEEDS_MIN_ID', '1:4:3');
//2.


//动态相关
//1.缓存
define('FEED_CACHE_MIN_FOLLOWS_ME_COUNT', 200);//粉丝数大于该数的用户在发布动态是缓存动态内容
define('FEED_CACHE_MIN_LIKES_COUNT', 100);//点赞数大于该数的动态需缓存内容
define('FEED_LIKES_COUNT', '2:1:1');
define('FEED_LIKES_SET', '2:1:2');
define('FEED_LIST', '2:2');
//2.状态
define('STATUS_UNCHECK', 0);
define('STATUS_CHECKED', 1);
define('STATUS_DELETED', 2);
