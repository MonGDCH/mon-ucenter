<?php

declare(strict_types=1);

namespace mon\ucenter\model;

use mon\util\Instance;
use mon\ucenter\UserCenter;

/**
 * 第三方账户模型
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class UserOpenAccountModel extends BaseModel
{
    use Instance;

    /**
     * 构造方法
     */
    public function __construct()
    {
        parent::__construct();
        // 定义表名
        $this->table = UserCenter::instance()->getConfig('table.user_open_account');
    }

    /**
     * 获取用户
     *
     * @param string $openid
     * @param integer $platform
     * @return array|false
     */
    public function getUser(string $openid, int $platform)
    {
        $info = $this->where('openid', $openid)->where('platform', $platform)->find();
        if (!$info) {
            $this->error = '用户未绑定使用';
            return false;
        }

        return $info;
    }

    /**
     * 是否已绑定
     *
     * @param integer $uid  用户ID
     * @param integer $platform 平台ID
     * @return boolean
     */
    public function isBind(int $uid, int $platform): bool
    {
        $isBind = $this->where('uid', $uid)->where('platform', $platform)->find();
        if (!$isBind) {
            return false;
        }

        return true;
    }

    /**
     * 用户绑定平台openid
     *
     * @param array $option 绑定参数
     * @param integer $uid  用户ID
     * @return boolean
     */
    public function bind(array $option, int $uid): bool
    {
        $check = $this->validate()->data($option)->scope('bind')->check();
        if (!$check) {
            $this->error = $this->validate()->getError();
            return false;
        }

        $isBind = $this->isBind($uid, $option['platform']);
        if ($isBind) {
            $this->error = '账号已绑定，如需修改，请解绑后重新绑定';
            return false;
        }
        // 绑定
        $save = $this->save([
            'uid'       => $uid,
            'openid'    => $option['openid'],
            'platform'  => $option['platform'],
        ]);
        if (!$save) {
            $this->error = '账号绑定平台openid失败';
            return false;
        }

        return true;
    }

    /**
     * 解除绑定
     *
     * @param integer $uid  用户ID
     * @param integer $platform 平台ID
     * @return boolean
     */
    public function unbind(int $uid, int $platform): bool
    {
        $check = $this->validate()->data(['platform' => $platform])->scope('platform')->check();
        if (!$check) {
            $this->error = $this->validate()->getError();
            return false;
        }

        $isBind = $this->isBind($uid, $platform);
        if (!$isBind) {
            $this->error = '账号未绑定平台openid';
            return false;
        }

        $del = $this->where('uid', $uid)->where('platform', $platform)->delete();
        if (!$del) {
            $this->error = '解除账号绑定失败';
            return false;
        }

        return true;
    }
}
