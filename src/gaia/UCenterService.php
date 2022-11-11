<?php

declare(strict_types=1);

namespace support\auth;

use mon\env\Config;
use mon\util\Instance;
use mon\ucenter\UCenter;

/**
 * 用户中心服务
 * 
 * @method \mon\ucenter\model\UserModel user() 获取用户模型
 * @method \mon\ucenter\model\UserLoginLogModel loginLog() 获取用户登录记录模型
 * @method \mon\ucenter\model\UserOpenAccountModel openAccount() 获取用户第三方账户信息模型
 * @method \mon\ucenter\model\UserRealnameAuthModel realnameAuth() 获取用户实名认证模型
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class UCenterService
{
    use Instance;

    /**
     * 缓存服务对象
     *
     * @var UCenter
     */
    protected $service;

    /**
     * 配置信息
     *
     * @var array
     */
    protected $config = [];

    /**
     * 构造方法
     */
    public function __construct()
    {
        $config = Config::instance()->get('ucenter', []);
        // 注册配置信息
        $this->register($config);
    }

    /**
     * 注册配置信息
     *
     * @param array $config
     * @return UCenterService
     */
    public function register(array $config): UCenterService
    {
        $this->config = array_merge($this->config, $config);
        if (is_string($this->config['database'])) {
            $dbconfig = Config::instance()->get('database.' . $this->config['database'], []);
            $this->config['database'] = $dbconfig;
        }

        return $this;
    }

    /**
     * 获取配置信息
     *
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * 获取缓存服务实例
     *
     * @return UCenter
     */
    public function getService(): UCenter
    {
        if (is_null($this->service)) {
            $this->service = UCenter::instance()->init($this->config);
        }

        return $this->service;
    }

    /**
     * 回调服务
     *
     * @param string $name      方法名
     * @param mixed $arguments 参数列表
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->getService(), $name], (array) $arguments);
    }
}
