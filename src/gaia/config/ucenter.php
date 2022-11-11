<?php

/*
|--------------------------------------------------------------------------
| 用户中心相关配置文件
|--------------------------------------------------------------------------
| 定义用户中心相关配置信息
|
*/
return [
    // 模型对应表名
    'table' => [
        // 用户模型
        'user'                  => 'user',
        // 用户实名验证模型
        'user_realname_auth'    => 'user_realname_auth',
        // 第三方开放平台账户模型
        'user_open_account'     => 'user_open_account',
        // 用户登录记录模型
        'user_login_log'        => 'user_login_log'
    ],
    // 添加用户或者修改基本信息时，判断唯一的数据字段，键为字段名，值为中文描述
    'unique_field'  => [
        'email'     => '邮箱',
        'moble'     => '手机号',
        'username'  => '用户名'
    ],
    // 用于加密生成推荐码的数值
    'inviter_code'          => 651423,
    // 推荐码最高记录层级，0则全部记录
    'inviter_level_limit'   => 3,
    // 新增用户时，如未输入密码，默认的密码
    'default_password'      => '123456',
    // 注册时，是否强制要求填入邀请码(参数名：code)
    'force_invite_code'     => false,
    // 注册用户默认status状态
    'register_user_status'  => 1,
    // 默认用户头像
    'default_avatar'        => '',
    // 登录失败次数限制
    'login_faild'           => [
        // 账号登录失败次数
        'account_error_limit'   => 5,
        // IP登录失败次数
        'ip_error_limit'        => 8,
        // 间隔时间多少分钟
        'login_gap'             => 5,
    ],
    // 数据库配置, 默认使用database.default
    'database' => 'default',
];
