<?php

declare(strict_types=1);

namespace mon\ucenter;

use PDO;
use mon\orm\Db;
use mon\util\Instance;

/**
 * 用户中心
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class UserCenter
{
    use Instance;

    /**
     * 初始化标志
     *
     * @var boolean
     */
    protected $init = false;
    /**
     * 配置信息
     *
     * @var array
     */
    protected $config = [
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
        ],
        // 用于加密生成推荐码的数值
        'inviter_code'          => 651423,
        // 推荐码最高记录层级，0则全部记录
        'inviter_level_limit'   => 3,
        // 新增用户时，如未输入密码，默认的密码
        'default_password'      => '123456',
        // 注册时，是否强制要求填入邀请码(参数名：code)
        'force_invite_code'       => false,
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

    /**
     * 构造方法
     *
     * @param array $config 配置信息
     */
    protected function __construct(array $config = [])
    {
        // 定义配置
        $this->config = array_merge($this->config, $config);
    }

    /**
     * 初始化
     *
     * @return UserCenter
     */
    public function init(): UserCenter
    {
        // 数据库断线重连
        if ($this->config['break_reconnect']) {
            Db::reconnect(true);
        }
        // 标志初始化
        $this->init = true;

        return $this;
    }

    /**
     * 是否已初始化
     *
     * @return boolean
     */
    public function isInit(): bool
    {
        return $this->init;
    }

    /**
     * 获取配置信息，支持'.'分割
     *
     * @param string $key 配置索引
     * @param mixed $default 默认值
     * @return mixed
     */
    public function getConfig(string $key = '', $default = '')
    {
        if (empty($key)) {
            return $this->config;
        }
        // 以"."分割，支持多纬度配置信息获取
        $name = explode('.', $key);
        $data = $this->config;
        for ($i = 0, $len = count($name); $i < $len; $i++) {
            $index = $name[$i];
            // 不存在配置节点，返回默认值
            if (!isset($data[$index])) {
                $data = $default;
                break;
            }
            $data = $data[$index];
        }

        return $data;
    }

    /**
     * 设置配置信息
     *
     * @param array $config 配置信息
     * @return UserCenter
     */
    public function setConfig(array $config): UserCenter
    {
        $this->config = array_merge($this->config, $config);
        return $this;
    }
}
