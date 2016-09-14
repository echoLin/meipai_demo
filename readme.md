
# Laravel 5.2 meipai - Demo



## Quck Installation
---

    git clone https://github.com/echoLin/meipai_demo meipai

    cd meipai

    composer install

    php artisan migrate

    php db:seed
   
   
   
## API
---
	1.用户基本信息（姓名、邮箱、美拍数、关注数、粉丝数）
	-get /user/{uid?}
	
	2.关注用户
	-post /follow/{follow_uid}
	
	3.取关用户
	-delete /follow/{follow_uid}
	
	4.拉取动态列表
	-get /feed/{max?}/{min?}
	
	5.获取动态内容
	-get /feed/{feed_id}
	
	6.发布动态
	-post /feed
	params:content(内容)
	
	7.删除动态
	-delete /feed/{feed_id}
	
	8.点赞动态
	-post /like/{feed_id}
	
	9.取赞动态
	-delete /like/{feed_id}
    
    
## Design
---


### Test

#### Development
	laravel 5.2
	PHP 7.0.10
	memcached 1.4.4
	redis 2.4.10
	nginx nginx/meitu1210
	mysql Ver 14.14 Distrib 5.1.73, for redhat-linux-gnu (x86_64) using readline 5.1

##### use wrk

	1.用户基本信息（姓名、邮箱、美拍数、关注数、粉丝数）
	-get /user/{uid?}
	
	Running 30s test @ http://192.168.41.214/user/1024
  	2 threads and 50 connections
  	Thread Stats    Avg        Stdev     Max        +/- Stdev
    Latency         179.90ms   38.12ms   537.26ms   84.02%
    Req/Sec         140.21     40.77     252.00     76.64%
  	8344 requests in 30.05s, 6.58MB read
	Requests/sec:    277.67
	Transfer/sec:    224.25KB

	
	2.关注用户
	-post /follow/{follow_uid}
	
	3.取关用户
	-delete /follow/{follow_uid}
	
	4.拉取动态列表
	-get /feed/{max?}/{min?}
	
	5.获取动态内容
	-get /feed/{feed_id}
	
	6.发布动态
	-post /feed
	-data {'content':'happy day~"}
	
	Running 30s test @ http://192.168.41.214/feed
  	2 threads and 50 connections
  	Thread Stats   Avg        Stdev     Max        +/- Stdev
    Latency        211.73ms   37.61ms   517.61ms   69.50%
    Req/Sec        118.32     26.24     191.00     65.83%
  	7055 requests in 30.01s, 1.43MB read
  	Non-2xx or 3xx responses: 7055
	Requests/sec:    235.06
	Transfer/sec:     48.89KB
	
	7.删除动态
	-delete /feed/{feed_id}
	
	Running 30s test @ http://192.168.41.214/feed/1609000000073100001
  	2 threads and 50 connections
  	Thread Stats   Avg       Stdev     Max        +/- Stdev
    Latency        67.67ms   46.25ms   338.26ms   62.67%
    Req/Sec        390.14    104.40    727.00     73.46%
  23326 requests in 30.06s, 4.74MB read
  Non-2xx or 3xx responses: 23326
Requests/sec:    775.92
Transfer/sec:    161.36KB
	
	8.点赞动态
	-post /like/{feed_id}
	
	9.取赞动态
	-delete /like/{feed_id}


#### Data





### Database
#### meipai
- User

| Field        | Type           | Des                 |
| ------------ |:--------------:| -------------------:|
| id           | int            | 自增                 |
| email        | varchar(255)   | 邮箱                 |
| name         | varchar(255)   | 昵称                 |
| password     | varchar(60)    | 密码                 |
| max_feed_id  | bigint         | 发布动态最大id        |
| min_feed_id  | bigint         | 发布动态最小id        |
| created_at   | timestamp      | 创建时间              |
| updated_at   | timestamp      | 更新时间              |

	CREATE TABLE `users` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
	`name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
	`password` varchar(60) COLLATE utf8_unicode_ci NOT NULL,
	`max_feed_id` bigint(20) NOT NULL,
	`min_feed_id` bigint(20) NOT NULL,
	`remember_token` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
	`created_at` timestamp NULL DEFAULT NULL,
	`updated_at` timestamp NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `users_email_unique` (`email`)
	)ENGINE=MyISAM AUTO_INCREMENT=5001 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	

#### follows 关系库
- follows_0000 ~ follows_1023

| Field        | Type           | Des                 |
| ------------ |:--------------:| -------------------:|
| id           | int            | 主键 自增            |
| uid          | int            | 关注人id             |
| follow_uid   | int            | 被关注人id            |
| created_at   | timestamp      | 创建时间              |
| updated_at   | timestamp      | 更新时间              |

	PS: xxxx =  uid % 2014;
	
	CREATE TABLE `follows_xxxx` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`uid` int(11) NOT NULL,`follow_uid` int(11) NOT NULL
	`created_at` timestamp NULL DEFAULT NULL,
	`updated_at` timestamp NULL DEFAULT NULL,
	PRIMARY KEY (`id`),UNIQUE KEY 
	`follows_xxxx_uid_follow_uid_unique` (`uid`,`follow_uid`)
	) ENGINE=MyISAM AUTO_INCREMENT=226 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


- follows_me_0000 ~ follows_me_2013

| Field        | Type           | Des                 |
| ------------ |:--------------:| -------------------:|
| id           | int            | 主键 自增            |
| uid          | int            | 关注人id             |
| follow_uid   | int            | 被关注人id            |
| created_at   | timestamp      | 创建时间              |
| updated_at   | timestamp      | 更新时间              |

	PS: xxxx = follow_uid % 1024;
	
	CREATE TABLE `follows_me_xxxx` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`uid` int(11) NOT NULL,
	`follow_uid` int(11) NOT NULL,
	`created_at` timestamp NULL DEFAULT NULL,
	`updated_at` timestamp NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `follows_me_xxxx_uid_follow_uid_unique`(`uid`,`follow_uid`)
	) ENGINE=MyISAM AUTO_INCREMENT=227 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	

#### feeds 动态库
- feeds_YYmm

| Field        | Type           | Des              |
| ------------ |:--------------:| ----------------:|
| id           | bigint     | 主键(yymm+uid+index)  |
| uid          | int            | 用户id               |
| content      | varchar(255)   | 内容               |
| status       | tinyint | 状态（1通过，0待审核，-1已删除）|
| created_at   | timestamp      | 创建时间              |
| updated_at   | timestamp      | 更新时间              |

	CREATE TABLE `feeds_xxxx` (
    `id` bigint(20) NOT NULL,
    `uid` int(11) NOT NULL,
    `status` tinyint(4) NOT NULL,
  	`content` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  	`created_at` timestamp NULL DEFAULT NULL,
  	`updated_at` timestamp NULL DEFAULT NULL,
   	 PRIMARY KEY (`id`),
  	KEY `feeds_xxxx_uid_index` (`uid`),
  	KEY `feeds_xxxx_created_at_index` (`created_at`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


- feeds_index_000~feeds_index_127

| Field        | Type           | Des              |
| ------------ |:--------------:| ----------------:|
| id           | bigint     | 主键(yymm+uid+index)  |
| uid          | int            | 用户id               |
| status       | tinyint | 状态（1通过，0待审核，-1已删除）|
| created_at   | timestamp      | 创建时间              |
| updated_at   | timestamp      | 更新时间              |

	PS：xxx = uid % 128;
	
	CREATE TABLE `feeds_index_xxx` (
	`feed_id` bigint(20) NOT NULL,
	`uid` int(11) NOT NULL,
	`status` tinyint(4) NOT NULL,
	`created_at` timestamp NULL DEFAULT NULL,
	`updated_at` timestamp NULL DEFAULT NULL,
	PRIMARY KEY (`feed_id`,`uid`),
    KEY `feeds_index_xxx_uid_index` (`uid`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


#### likes 点赞库
- likes_0000~likes_1023

| Field        | Type           | Des                 |
| ------------ |:--------------:| -------------------:|
| id           | int            | 主键 自增            |
| uid          | int            | 点赞人id             |
| feed_id      | bigint         | 动态id               |
| created_at   | timestamp      | 创建时间              |
| updated_at   | timestamp      | 更新时间              |

	PS: xxxx = uid % 1028;
	
	CREATE TABLE `likes_xxxx` (
  	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  	`uid` int(11) NOT NULL,
  	`feed_id` bigint(20) NOT NULL,
  	`created_at` timestamp NULL DEFAULT NULL,
  	`updated_at` timestamp NULL DEFAULT NULL,
  	PRIMARY KEY (`id`),
  	UNIQUE KEY `likes_xxxx_uid_feed_id_unique` (`uid`,`feed_id`),
  	KEY `likes_xxxx_uid_index` (`uid`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



- likes_feed_0000~likes_2013

| Field        | Type           | Des                 |
| ------------ |:--------------:| -------------------:|
| id           | int            | 主键 自增            |
| uid          | int            | 点赞人id             |
| feed_id      | bigint         | 动态id               |
| created_at   | timestamp      | 创建时间              |
| updated_at   | timestamp      | 更新时间              |
	
	PS: xxxx = feed_id % 1028;
	
	CREATE TABLE `likes_feed_xxxx` (
  	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  	`uid` int(11) NOT NULL,
  	`feed_id` bigint(20) NOT NULL,
  	`created_at` timestamp NULL DEFAULT NULL,
  	`updated_at` timestamp NULL DEFAULT NULL,
  	PRIMARY KEY (`id`),
  	UNIQUE KEY `likes_feed_xxxx_uid_feed_id_unique` (`uid`,`feed_id`),
  	KEY `likes_feed_xxxx_feed_id_index` (`feed_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;