
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
	-get /feeds/{start_time?}{end_time?}{uid?}
	
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

1.发布动态、删除动态、关注、取关、点赞、取消赞均使用队列  
2.用户信息如粉丝数、关注数、动态数均使用MC缓存  
3.动态发布者的粉丝大于FEED_CACHE_MIN_FOLLOWS_ME_COUNT时缓存动态  
4.获取动态列表采用全拉模式  
5.动态id发号器:根据uid%32插入表feeds_id得到自增id,动态id=ym+uid+自增id


### Database
	User 5k
	Follow 10w
	Feed 2w
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
	)ENGINE=InnoDB AUTO_INCREMENT=5001 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	

#### follows 关系库
- follows_0000 ~ follows_1023

| Field        | Type           | Des                 |
| ------------ |:--------------:| -------------------:|
| id           | int            | 主键 自增            |
| uid          | int            | 关注人id             |
| follow_uid   | int            | 被关注人id            |
| created_at   | timestamp      | 创建时间              |
| updated_at   | timestamp      | 更新时间              |

	PS: xxxx =  uid % 1024;
	
	CREATE TABLE `follows_xxxx` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`uid` int(11) NOT NULL,`follow_uid` int(11) NOT NULL
	`created_at` timestamp NULL DEFAULT NULL,
	`updated_at` timestamp NULL DEFAULT NULL,
	PRIMARY KEY (`id`),UNIQUE KEY 
	`follows_xxxx_uid_follow_uid_unique` (`uid`,`follow_uid`)
	) ENGINE=InnoDB AUTO_INCREMENT=226 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


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
	) ENGINE=InnoDB AUTO_INCREMENT=227 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	

#### feeds 动态库
- feeds_id_xxx

| Field        | Type           | Des              |
| ------------ |:--------------:| ----------------:|
| id           | bigint         | 主键              |

	
	PS: xxx = uid % 32
	
	CREATE TABLE `feeds_id_xxx` (
  	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  	PRIMARY KEY (`id`)
	) ENGINE=InnoDB AUTO_INCREMENT=595 DEFAULT CHARSET=utf8 	COLLATE=utf8_unicode_ci;


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
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


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
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


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


## Test

#### Development
	laravel 5.2
	PHP 7.0.10
	memcached 1.4.4 
		-p:11211
		-c:1024
		-m:63MB
	redis 2.4.10
	nginx nginx/meitu1210
	mysql Ver 14.14 Distrib 5.1.73, for redhat-linux-gnu (x86_64) using readline 5.1

##### use wrk

	1.用户基本信息（姓名、邮箱、美拍数、关注数、粉丝数）
	-get /user/{uid?}
		PS:/user 随机获取用户信息
	
	Running 30s test @ http://192.168.41.214/user
  	2 threads and 50 connections
  	Thread Stats   Avg        Stdev     Max        +/- Stdev
    Latency        125.45ms   36.58ms   523.76ms   85.43%
    Req/Sec        201.40     44.91     350.00     75.92%
  	12025 requests in 30.07s, 5.53MB read
	Requests/sec:    399.88
	Transfer/sec:    188.24KB

	
	2.关注用户
	-post /follow/{follow_uid}
	
	Running 30s test @ http://192.168.41.214/follow/2048
  	2 threads and 50 connections
  	Thread Stats   Avg       Stdev     Max       +/- Stdev
    Latency       133.45ms   79.50ms  408.76ms   62.58%
    Req/Sec       191.05     71.33    404.00     69.83%
  	11324 requests in 30.04s, 3.13MB read
	Requests/sec:    376.97
	Transfer/sec:    106.80KB
	
	3.取关用户
	-delete /follow/{follow_uid}
	
	Running 30s test @ http://192.168.41.214/follow/2048
  	2 threads and 50 connections
  	Thread Stats   Avg       Stdev     Max        +/- Stdev
    Latency       159.59ms   30.48ms   339.97ms   81.22%
    Req/Sec       156.87     45.79     274.00     65.83%
  	9380 requests in 30.04s, 2.40MB read
	Requests/sec:    312.21
	Transfer/sec:     81.70KB
	
	4.拉取动态列表
	-get /feed/{start_time?}/{end_time?}/{uid?}
	
	- 随机用户拉取其关注者的动态列表
	Running 30s test @ http://192.168.41.214/feeds
  	2 threads and 50 connections
  	Thread Stats   Avg        Stdev     Max        +/- Stdev
    Latency        285.90ms   51.07ms   888.68ms   96.10%
    Req/Sec        88.37      23.69     161.00     74.75%
  	5249 requests in 30.08s, 40.68MB read
	Requests/sec:    174.48
	Transfer/sec:      1.35MB
	
	-拉取某一用户的动态列表
	Running 30s test @ http://192.168.41.214/feeds/0/0/4447
  	2 threads and 50 connections
  	Thread Stats   Avg        Stdev     Max        +/- Stdev
    Latency        138.83ms   74.96ms   487.47ms   61.19%
    Req/Sec        181.26     56.22     340.00     69.63%
  	10822 requests in 30.01s, 6.57MB read
	Requests/sec:    360.58
	Transfer/sec:    224.29KB
	
	
	
	5.获取某一动态内容
	-get /feed/{feed_id}
	Running 30s test @ http://192.168.41.214/feed/1609000000102400483
  	2 threads and 50 connections
  	Thread Stats   Avg      Stdev     Max   +/- Stdev
    Latency   155.50ms   53.33ms 520.40ms   81.63%
    Req/Sec   161.90     51.34   290.00     64.20%
  	9659 requests in 30.06s, 5.62MB read
	Requests/sec:    321.27
	Transfer/sec:    191.37KB
	
	
	6.发布动态
	-post /feed
	-data {'content':'happy day~"}
	
	Running 30s test @ http://192.168.41.214/feed
  	2 threads and 50 connections
  	Thread Stats   Avg        Stdev     Max        +/- Stdev
    Latency        140.82ms   62.72ms   601.40ms   72.25%
    Req/Sec        180.77     53.83     343.00     74.54%
  	10737 requests in 30.06s, 2.99MB read
	Requests/sec:    357.18
	Transfer/sec:    101.76KB
	
	7.删除动态
	-delete /feed/{feed_id}
	
	Running 30s test @ http://192.168.41.214/feed/1609000000073100001
  	2 threads and 50 connections
  	Thread Stats   Avg        Stdev     Max        +/- Stdev
    Latency        137.80ms   78.72ms   425.99ms   68.77%
    Req/Sec        182.34     80.08     431.00     65.14%
  	10873 requests in 30.04s, 2.86MB read
	Requests/sec:    361.93
	Transfer/sec:     97.54KB
	
	8.点赞动态
	-post /like/{feed_id}
	
	Running 30s test @ http://192.168.41.214/like/1609000000102400483
  	2 threads and 50 connections
  	Thread Stats   Avg       Stdev     Max        +/- Stdev
    Latency       100.13ms   85.03ms   490.17ms   47.05%
    Req/Sec       265.86     125.65    510.00     57.24%
  	15835 requests in 30.06s, 4.22MB read
	Requests/sec:    526.70
	Transfer/sec:    143.88KB
	
	9.取赞动态
	-delete /like/{feed_id}
	
	Running 30s test @ http://192.168.41.214/like/1609000000102400483
  	2 threads and 50 connections
  	Thread Stats   Avg        Stdev     Max        +/- Stdev
    Latency        110.25ms   28.91ms   301.28ms   77.90%
    Req/Sec        227.43     46.96     353.00     76.81%
  	13575 requests in 30.01s, 3.63MB read
	Requests/sec:    452.34
	Transfer/sec:    123.86KB
	
	


###总结
PHP框架越重，性能相对就越低，因为重型框架会在解析时调用非常多的类、方法和自定义函数，导致性能严重下降。 
 
 之前使用过YAF、ThinkPHP开发过项目，所以本着学习的心态使用了传说中最为优美的，但是在主流框架，如yii、thinkphp、yaf等中性能最差的laravel框架。  

#####针对laravel进行性能提升：
* 1.使用最新的PHP7，laravel在php7下的性能比原先的5.6提高了54%。
* 2.让PHP7更快([鸟哥的博客](http://www.laruence.com/2015/12/04/3086.html))
	- （1）启用Zend Opcache
	- （2）开启HugePages,然后开启Opcache的huge_code_pages  
	  	 `sudo sysctl vm.nr_hugepages=512	`     
	 	 `cat /proc/meminfo | grep Huge`     
		 分配512个预留的大页内存  
		 然后在php.ini中加入  
		 `opcahce.huge_code_pages=1`  
		 这样PHP会把自身的text段以及内存分配中的huge都采用大内存页来保存，减少TLB miss，从而提高性能。
	- （3）使用最新的编译器，我使用了gcc4.8.2  
		只有4.8以上PHP才会开启Global Register for opline end execute_data支持。
		
* 3.优化laravel框架
	- (1) Stone  [git](https://github.com/StoneGroup/stone) [文档](https://chefxu.gitbooks.io/stone-docs/content/install_stone_in_laravel5.html)  [使用教程](https://segmentfault.com/a/1190000005826835)  
	
		Stone主要的优化点是框架的初始化和释放。Stone作为独立的进程单独启动，在框架资源初始化结束后再开启FastCGI服务，这样，新的请求过来是直接从资源是直接从资源初始化结束后的状态开始，避免每次请求去做资源初始化的事情。
		
		Stone的风险：Stone是常驻内存的，Stone中cookie都被维护在一个数组中，在原来的PHP-FPM下，不会有任何问题，因为请求结束后将释放所有资源，但Stone内存之后，请求的资源没有在请求结束释放，因为cookie对象呗IOC容器引用，PHP GC不会回收这部分内存，因此下一个请求再使用Cookie的时候获取的是同一个Cookie对象。

	
		Stone依赖于swoole和runkit扩展，而runkit暂不支持php7,github上虽有runkit7，但是并不是对所有7.0.x都支持。  
		
		 在本demo中原想使用Stone,但是，由于runkit7对php7.0.10好像并不支持，扩展的时候报错。根据Stone的测试数据来看，使用Stone可以带来喜人的性能优化，值得一试。    
		 
	- (2) [LaravelFly](https://github.com/scil/LaravelFly)    
	
	
#####瓶颈
1.框架的瓶颈。laravel封装过重，实际开发中还是使用轻框架如yaf或自定义框架为上策。  
2.发号器的优化。  
3.读写的瓶颈。demo中没有使用主从同步。  
4.分布式事务最终一致性。


	
   