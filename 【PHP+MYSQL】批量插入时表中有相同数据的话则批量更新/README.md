#### 应用场景
有时候我们向数据库插入记录时，有时会有这种需求，当符合某种条件的数据存在时，去修改它，不存在时，则新增数据的情况。
比如说系统配置则一块,如有下表：

```
CREATE TABLE `system_config` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(60) NOT NULL DEFAULT '' COMMENT '配置标题',
  `details` varchar(255) NOT NULL DEFAULT '' COMMENT '配置详细用途说明',
  `set_key` varchar(120) NOT NULL DEFAULT '' COMMENT '设置的key',
  `set_value` varchar(255) NOT NULL DEFAULT '' COMMENT '设置的value，如果配置值为数组的话，自行转为json存入字段',
  `type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '设置类型（\r\n1、站点设置，\r\n2、SEO设置，\r\n3、版权设置，\r\n4、运营设置，\r\n5、注册与访问，\r\n6、上传设置，\r\n7、商家服务...）',
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `set_key` (`set_key`) USING BTREE COMMENT 'key唯一索引',
  KEY `type` (`type`)
) ENGINE=MyISAM AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COMMENT='系统配置表';
```
现在需要编辑配置，如果可以批量的编辑，并且存在则更新，否之则新增，那么久可以使用 INSERT ... ON DUPLICATE KEY UPDATE 语句。完整语句如下：
```

INSERT INTO system_config (title,details,set_key,set_value,type,created_at,updated_at) values ("123","123","123","123","123","2018-11-20 06:06:46","2018-11-20 06:06:46"),("12233","123","12334","123","123","2018-11-20 06:06:46","2018-11-20 06:06:46") ON DUPLICATE KEY UPDATE title=VALUES(title),details=VALUES(details),set_key=VALUES(set_key),set_value=VALUES(set_value),type=VALUES(type),updated_at=VALUES(updated_at);
```
下面给出一个批量操作的类，本来是用在laravel中的，所以如果需要用的话直接改下执行语句的方法就行
[github 传送门](https://github.com/liumenglei/TheBlogSample/tree/master/%E3%80%90PHP+MYSQL%E3%80%91%E6%89%B9%E9%87%8F%E6%8F%92%E5%85%A5%E6%97%B6%E8%A1%A8%E4%B8%AD%E6%9C%89%E7%9B%B8%E5%90%8C%E6%95%B0%E6%8D%AE%E7%9A%84%E8%AF%9D%E5%88%99%E6%89%B9%E9%87%8F%E6%9B%B4%E6%96%B0)
