CREATE TABLE IF NOT EXISTS `user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `inviter_uid` varchar(250) NOT NULL DEFAULT '' COMMENT '邀请人uid，按层级从下往上排列的uid数组，即第一个是直接上级',
  `username` varchar(20) NOT NULL DEFAULT '' COMMENT '用户名',
  `moble` varchar(20) NOT NULL DEFAULT '' COMMENT '手机号',
  `email` varchar(100) NOT NULL DEFAULT '' COMMENT '邮箱',
  `password` char(32) NOT NULL COMMENT '密码',
  `pay_password` char(32) NOT NULL DEFAULT '' COMMENT '交易密码',
  `salt` varchar(10) NOT NULL COMMENT '加密盐',
  `token` char(32) NOT NULL DEFAULT '' COMMENT '登录token',
  `nickname` varchar(30) NOT NULL DEFAULT '' COMMENT '昵称',
  `level` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '等级',
  `avatar` varchar(250) NOT NULL DEFAULT '' COMMENT '头像',
  `sex` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '性别，0保密，1男，2女',
  `register_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '注册方式, 0系统添加, 1手机号注册, 2邮箱注册，3用户名注册',
  `register_ip` varchar(50) NOT NULL DEFAULT '' COMMENT '注册IP',
  `login_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后登录时间',
  `login_ip` varchar(20) NOT NULL DEFAULT '' COMMENT '最后登录的IP',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '0审核中，1正常，2禁用，3审核未通过',
  `comment` varchar(250) NOT NULL DEFAULT '' COMMENT '备注信息，可用户记录一些注册来源信息，或用户描述',
  `update_time` int(10) unsigned NOT NULL COMMENT '更新时间',
  `create_time` int(10) unsigned NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `moble` (`moble`) USING BTREE,
  KEY `email` (`email`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=985 DEFAULT CHARSET=utf8mb4 COMMENT='用户表';


CREATE TABLE IF NOT EXISTS `user_open_account` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL COMMENT '用户ID',
  `openid` varchar(108) NOT NULL COMMENT '第三方平台openid',
  `platform` tinyint(3) unsigned NOT NULL COMMENT '平台类型,0:QQ,1:wx',
  `access_token` varchar(255) NOT NULL DEFAULT '' COMMENT 'AccessToken',
  `refresh_token` varchar(255) NOT NULL DEFAULT '' COMMENT 'RefreshToken',
  `expires_in` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '有效期',
  `update_time` int(10) unsigned NOT NULL,
  `create_time` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `item` (`uid`,`openid`,`platform`) USING BTREE,
  KEY `userType` (`uid`,`platform`) USING BTREE,
  KEY `openidType` (`openid`,`platform`) USING BTREE,
  KEY `user` (`uid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=109 DEFAULT CHARSET=utf8mb4 COMMENT='用户关联第三方平台表';


CREATE TABLE IF NOT EXISTS `user_realname_auth` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL COMMENT '关联用户ID',
  `auth_type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '用户类型：0 个人用户 1 企业用户',
  `auth_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '认证状态：0 未认证 1 认证通过 2 认证失败',
  `auth_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '认证通过时间',
  `real_name` varchar(250) NOT NULL DEFAULT '' COMMENT '真实姓名/企业名称',
  `identity` varchar(250) NOT NULL DEFAULT '' COMMENT '身份证号码/营业执照号码',
  `id_card_front` varchar(250) NOT NULL DEFAULT '' COMMENT '身份证正面照URL',
  `id_card_back` varchar(250) NOT NULL DEFAULT '' COMMENT '身份证反面照URL',
  `id_card_hand` varchar(250) NOT NULL DEFAULT '' COMMENT '手持身份证照片URL',
  `license` varchar(250) NOT NULL DEFAULT '' COMMENT '营业执照URL',
  `contact_person` varchar(250) NOT NULL DEFAULT '' COMMENT '联系人姓名',
  `contact_mobile` varchar(250) NOT NULL DEFAULT '' COMMENT '联系人手机号码',
  `contact_email` varchar(250) NOT NULL DEFAULT '' COMMENT '联系人邮箱',
  `comment` varchar(250) NOT NULL DEFAULT '' COMMENT '备注信息',
  `update_time` int(10) unsigned NOT NULL COMMENT '数据更新时间',
  `create_time` int(10) unsigned NOT NULL COMMENT '数据创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uid` (`uid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户实名认证表';


CREATE TABLE IF NOT EXISTS `user_login_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `uid` int(10) unsigned NOT NULL COMMENT '用户ID',
  `type` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '日志类型，0退出登录，1登录成功，2密码错误...更多类型',
  `action` varchar(100) NOT NULL COMMENT '操作类型',
  `content` varchar(255) NOT NULL DEFAULT '' COMMENT '描述信息',
  `ip` varchar(20) NOT NULL DEFAULT '' COMMENT 'IP地址',
  `ua` varchar(250) NOT NULL DEFAULT '' COMMENT '浏览器请求头user-agent',
  `create_time` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户登录表';

