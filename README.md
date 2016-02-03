##环境
linux环境
安装php，php需要的扩展：pcntl、posix、redis、pdo
建议安装libevent，高并发性更好
安装redis并启动

##需要修改
- 1.数据库配置 Applications/Config/Db.php
- 2.redis配置: Applications/Config/Redis.php

##注意事项
用户账号仅支持 字母、数字、下划线、英文.  例如(cui_hong.bo)

##需要的库表 
- 库： webChat （需要手动建立）
- 表：webchat_message年月       //（自动生成）用来存储聊天记录
- 表：queue_deamon_status  //（自动生成）用来存储队列状态
- 表： webchat_user		 //（手动建立）用来存储用户数据
-  		CREATE TABLE `webchat_user` (
-		  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '用户id',
-		  `accountid` varchar(40) NOT NULL COMMENT '域账户',
-		  `pwd` varchar(40) NOT NULL,
-		  `username` varchar(40) NOT NULL COMMENT '姓名',
-		  `dept` varchar(40) NOT NULL COMMENT '部门',
-		  `tel` varchar(40) NOT NULL COMMENT '分机号',
-		  `mobile` varchar(100) NOT NULL COMMENT '移动电话（用,分隔）',
-		  `email` varchar(255) NOT NULL COMMENT '邮箱',
-		  `deptDetail` varchar(128) NOT NULL DEFAULT '' COMMENT '详细部门从一级到n级，用英文,号分割',
-		  `updateTime` int(11) NOT NULL DEFAULT '0',
-		  PRIMARY KEY (`uid`)
-		) ENGINE=MyISAM AUTO_INCREMENT=10727 DEFAULT CHARSET=utf8

##运行：
###一、启动聊天服务。根目录下
	（以debug方式启动 ） 
	```php start.php start  ```
	（以daemon方式启动  ）
	```php start.php start -d ```
	还可以使用 stop reload status 等命令
	
###二、聊天数据保存永久保存。/Vendors/Redis/ 下运行
           临时运行    	``` php doQuene ```
	daemon方式运行	```nohub php doQuene &```


##消息发送和接受机制
###登录
wc_ws.send({"type":"login","clientName":wc_loginName})

###前端发送消息机制
- chatid:chatid一共两种情况
-	       如果是单人聊天则chatid就是两个人用‘--’连接的字串（且注意俩名称是经过sort排序的）cuihb--xieyx
-	       如果是群组聊天则chatid就是唯一的字串(例如群组+创建群的时间)cuihb-63756323 根据这个字串可以从redis中获取成员
- wc_ws.send(JSON.stringify({"type":"say","chatid":chatid,"content":msg}));

###前端获取redis历史消息记录
wc_ws.send(JSON.stringify({"type":"history","chatid":chatid}));


##实现的功能：
- 1、所有聊天历史记录永久保存
- 2、记录用户最近联系人，用户每次登陆即可加载
- 3、支持拉群
- 4、支持新消息、离线消息提醒
- 5、支持用户上线提醒
- 6、消息队列监控

##实现方法：

###1.消息永久保存
 所有用户聊天消息都会存放到一个消息队列中，处理消息队列的程序采用始终循环的方式，将消息队列数据中的数据弹出并存到数据库表中。
 
 记录消息的表中有一个chatid字段，这个chatid就是用来记录每一路聊天的唯一标记，比如zhangsan、lisi两人之间的聊天，
 那么他们的chatid就是lisi--zhangsan。其中array('lisi','zhangsan')是
 经过排序的，即不管是lisi对zhangsan说还是zhangsan对lisi说生成的chatid都是一样的。
 如果是群聊天，则在第一次建群时的群主+时间戳 会生成本群的 chatid 群成员存储在 redis 的hash数据类型中
  如果队列中没有消息，则处理程序会自动sleep，减少服务器压力
  
###2.记录用户最近联系人
 在处理消息队列时记录用户最近联系人，循环每一条消息所涉及的用户群，然后将用户群存于相关用户的redis有序集合中，因为集合不允许重复的值存在，
 redis中的几种数据结构只有有序集合可以实现根据score更新元素的顺序。（集合做不到、列表则需要判断，删除，添加）

###3.历史记录
 也是在处理消息队列时处理。任何一路对话的最新50条都会存在redis的列表中，redis的键值也会用到上面的chatid。
 因为是基于浏览器的聊天，每刷新页面本地的聊天记录都会清空，第一次加载的记录需要远程取，就可以直接从redis中取，
 之后只要用户不刷新页面，那么聊天记录都是通过js存在本地，避免从远程取。如果要看以前的记录则需要向数据库中取。
 
###4.拉群
 用户名都是唯一的，根据用户名即可实现群发
 
 ###5、新消息、离线消息提醒
 新消息提醒就是当用户在线时，新消息到来时，如果最近联系人列表中有对方，则将未读消息数加1，如果没有，则将对方加在最近联系人列表，并未读消息数加1。
 
 离线消息提醒，当A向B发送消息时，会用过B的用户名取client_id来判断B是否在线，如果B不在线，则会将此消息压入属于B的离线消息列表，
 离线消息列表最多保留50条，当B登陆时会加载离线消息列表并判处最近联系人列表中有A，则将A未读消息加1，如果没有，则将A加在最近联系人列表，并未读消息加1。
 
 群离线消息的提醒的实现与双人对话的离线消息提醒相类似。

###6、用户上线提醒
 用户上线时向所有在线用户广播。
 
###7、队列监控
 监控该消息队列总共处理消息数量
 监控当天处理消息数量
 监控该消息队列是否还活着
