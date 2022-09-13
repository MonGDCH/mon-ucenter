# mon-ucenter

基于`mongdch/mon-orm`的用户中心SDK

### 使用

1. 导入`database/database.sql`数据库文件，创建相关表

2. 定义初始化配置

```php

$config = [
    // 模型对应表名
    'table' => [
        // 用户模型
        'user' => 'user',
        // 用户实名验证模型
        'user_realname_auth' => 'user_realname_auth',
        // 第三方开放平台账户模型
        'user_open_account' => 'user_open_account',
        // 用户登录记录模型
        'user_login_log' => 'user_login_log'
    ],
    // 数据库断开自动重连
    'break_reconnect' => false,
    // 数据库配置
    'database' => [
        // 数据库类型，只支持mysql
        'type'          => 'mysql',
        // 服务器地址
        'host'          => '127.0.0.1',
        // 数据库名
        'database'      => 'test',
        // 用户名
        'username'      => 'root',
        // 密码
        'password'      => 'root',
        // 端口
        'port'          => '3306',
        // 数据库连接参数
        'params'        => [],
        // 数据库编码默认采用utf8
        'charset'       => 'utf8mb4',
        // 返回结果集类型
        'result_type'   => PDO::FETCH_ASSOC,
        // 是否开启读写分离
        'rw_separate'   => false,
        // 查询数据库连接配置，二维数组随机获取节点覆盖默认配置信息
        'read'          => [],
        // 写入数据库连接配置，同上，开启事务后，读取不会调用查询数据库配置
        'write'         => []
    ],
    // 添加用户或者修改基本信息时，判断唯一的数据字段，键为字段名，值为中文描述
    'unique_field'          => [
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
    ]
];

// 定义配置，运行初始化
\mon\ucenter\UCenter::instance()->setConfig($config)->init();

```

3. 调用相关模型API接口

```php

// example

$loginInfo = [];
\mon\ucenter\UCenter::instance()->user()->login($loginInfo);

```