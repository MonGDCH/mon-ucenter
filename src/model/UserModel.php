<?php

declare(strict_types=1);

namespace mon\ucenter\model;

use mon\util\IdCode;
use mon\util\Common;
use mon\ucenter\UCenter;
use mon\orm\exception\DbException;
use mon\ucenter\exception\UCenterException;

/**
 * 用户模型
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class UserModel extends BaseModel
{
    /**
     * 构造方法
     */
    public function __construct()
    {
        parent::__construct();
        // 定义表名
        $this->table = UCenter::instance()->getConfig('table.user');
    }

    /**
     * 查询信息
     *
     * @param array $where where条件
     * @param array $field 查询字段
     * @return array
     */
    public function getInfo(array $where, array $field = ['*']): array
    {
        return $this->where($where)->field($field)->find();
    }

    /**
     * 查询用户列表
     *
     * @param array $option 请求参数
     * @param boolean $forGroup 是否为组别查询
     * @return array
     */
    public function queryList(array $option = []): array
    {
        $limit = isset($option['limit']) ? intval($option['limit']) : 10;
        $page = isset($option['page']) && is_numeric($option['page']) ? intval($option['page']) : 1;
        // 查询
        $list = $this->scope('list', $option)->page($page, $limit)->select();
        $total = $this->scope('list', $option)->count('user.id');

        return [
            'list'      => $list,
            'total'     => $total,
            'pageSize'  => $limit,
            'page'      => $page
        ];
    }

    /**
     * 用户登录
     *
     * @param array $option 登录参数
     * @param string $ip    登录客户端IP
     * @param string $ua    登录客户端ua
     * @throws UCenterException
     * @return mixed 成功返回登录信息，失败返回false
     */
    public function login(array $option, string $ip = '', string $ua = '')
    {
        $check = $this->validate()->scope('login')->data($option)->check();
        if (!$check) {
            $this->error = $this->validate()->getError();
            return false;
        }
        // 获取用户信息
        switch ($option['login_type']) {
            case '1':
                // 手机号登录
                if (!check('moble', $option['username'])) {
                    $this->error = '请输入合法的手机号码';
                    return false;
                }
                $where['moble'] = $option['username'];
                break;
            case '2':
                // 邮箱登录
                if (!check('email', $option['username'])) {
                    $this->error = '请输入合法的邮箱地址';
                    return false;
                }
                $where['emial'] = $option['username'];
                break;
            case '3':
                // 用户名登录
                $where['username'] = $option['username'];
                break;
            default:
                $this->error = '未知登录方式';
                return false;
        }
        $userInfo = $this->getInfo($where);
        if (!$userInfo) {
            $this->error = '用户不存在';
            return false;
        }
        // 判断用户状态
        if ($userInfo['status'] != '1') {
            switch ($userInfo['status']) {
                case '0':
                    $this->error = '用户审核中';
                    break;
                case '2':
                    $this->error = '用户已禁用';
                    break;
                case '3':
                    $this->error = '用户审核未通过';
                    break;
                default:
                    $this->error = '用户状态未知错误';
                    break;
            }
            return false;
        }
        // 验证账号是否禁止登录
        if (!UserLoginLogModel::instance()->checkDisableAccount($userInfo['id'])) {
            $this->error = UserLoginLogModel::instance()->getError();
            return false;
        }
        // 验证密码
        if ($userInfo['password'] != $this->encodePassword($option['password'], $userInfo['salt'])) {
            // 记录登录错误日志
            UserLoginLogModel::instance()->record([
                'uid' => $userInfo['id'],
                'type' => 2,
                'action' => '登录密码错误',
                'ip' => $ip,
                'ua' => $ua
            ]);
            $this->error = '用户名密码错误';
            return false;
        }

        // 定义登陆信息
        $loginTime = time();
        $login_token = $this->encodeLoginToken($userInfo['id'], $ip);
        $this->startTrans();
        try {
            // 更新用户信息
            $saveLogin = $this->save([
                'login_time'    => $loginTime,
                'login_ip'      => $ip,
                'login_token'   => $login_token
            ], ['id' => $userInfo['id']]);
            if (!$saveLogin) {
                $this->rollBack();
                $this->error = '登陆失败';
                return false;
            }

            // 记录登录日志
            $record = UserLoginLogModel::instance()->record([
                'uid' => $userInfo['id'],
                'type' => 2,
                'action' => '登录成功',
                'ip' => $ip,
                'ua' => $ua
            ]);
            if (!$record) {
                $this->rollback();
                $this->error = '记录登录日志失败';
                return false;
            }

            $this->commit();

            $userInfo['login_time'] = $loginTime;
            $userInfo['login_ip'] = $ip;
            $userInfo['login_token'] = $login_token;
            return $userInfo;
        } catch (DbException $e) {
            $this->rollback();
            throw new UCenterException('登录异常', UCenterException::LOGIN_ERROR, $e);
        }
    }

    /**
     * 添加用户
     *
     * @param array $option 请求参数
     * @param boolean $useDefaultPwd 使用使用默认密码
     * @param string $ip    客户端IP
     * @param array $allow  数据库运行操作的字段
     * @return integer|false 成功返回用户ID，失败返回false
     */
    public function add(array $option, bool $useDefaultPwd = false, string $ip = '', array $allow = [])
    {
        if ((!isset($option['email']) || empty($option['email'])) && (!isset($option['moble']) || empty($option['moble']))) {
            $this->error = '邮箱或者手机号必须设置一种';
            return false;
        }

        // 判断使用默认密码
        $password = isset($option['password']) ? $option['password'] : ($useDefaultPwd ? UCenter::instance()->getConfig('default_password') : '');
        if (empty($password)) {
            $this->error = '密码不能为空';
            return false;
        }
        // 判断用重复数据
        if (!$this->checkUniqueField($option)) {
            return false;
        }
        // 判断是否存在推荐人
        if (isset($option['inviter_uid']) && $option['inviter_uid'] && check('int', $option['inviter_uid'])) {
            $inviter_uid = $option['inviter_uid'];
            // 查询推荐人推荐信息
            $inviterInfo = $this->getInfo(['id' => $inviter_uid], ['id', 'inviter_uid']);
            if ($inviterInfo) {
                $inviterList = explode(',', $inviterInfo['inviter_uid']);
                array_unshift($inviterList, $inviter_uid);
                // 层级截取
                if (UCenter::instance()->getConfig('inviter_level_limit', 0)) {
                    $inviterList = array_slice($inviterList, 0, UCenter::instance()->getConfig('inviter_level_limit'));
                }
                $inviter_uid = implode(',', $inviterList);
            }
            $option['inviter_uid'] = $inviter_uid;
        }

        // 生成密码
        $option['salt'] = Common::instance()->randString(6, 5);
        $option['password'] = $this->encodePassword($password, $option['salt']);
        if (!empty($option['pay_password'])) {
            $option['pay_password'] = $this->encodePassword($option['pay_password'], $option['salt']);
        }

        // 记录添加方式
        $option['register_type'] = isset($option['register_type']) ? intval($option['register_type']) : 0;
        $option['register_ip'] = $ip;

        // 判断是否存在nickname，不存在则随机生成昵称
        if (!isset($option['nickname']) || empty($option['nickname'])) {
            $option['nickname'] = '用户' . Common::instance()->randString(10, 5);
        }

        // 判断是否存在avatar，不存在则使用默认头像avatar
        if (!isset($option['avatar']) || empty($option['avatar'])) {
            $option['avatar'] = UCenter::instance()->getConfig('default_avatar');
        }

        // $allow = [
        //     'email', 'moble', 'password', 'pay_password', 'nickname', 'level', 'avatar',
        //     'sex', 'salt', 'comment', 'register_type', 'register_ip', 'inviter_uid', 'status'
        // ];
        $add_uid = $this->allowField($allow)->save($option, null, 'id');
        if (!$add_uid) {
            $this->error = '用户新增失败';
            return false;
        }

        return $add_uid;
    }

    /**
     * 用户注册(邮箱、手机号)
     *
     * @param array $option 注册参数
     * @param string $ip    客户端IP
     * @param array $allow  数据库运行操作的字段
     * @return integer|false 成功返回用户ID，失败返回false
     */
    public function register(array $option, string $ip = '', array $allow = [])
    {
        // 校验参数
        $check = $this->validate()->scope('register')->data($option)->check();
        if (!$check) {
            $this->error = $this->validate()->getError();
            return false;
        }

        // 判断是否需要填写邀请码
        if (UCenter::instance()->getConfig('force_invite_code', false) && (!isset($option['code']) || empty($option['code']))) {
            $this->error = '请输入邀请码';
            return false;
        }

        // 获取操作的字段
        $saveField = [
            'password'      => $option['password'],
            'register_type' => $option['register_type']
        ];
        switch ($option['register_type']) {
            case '1':
                $saveField['moble'] = $option['username'];
                break;
            case '2':
                $saveField['email'] = $option['username'];
                break;
            case '3':
                $saveField['username'] = $option['username'];
                break;
            default:
                $this->error = '注册方式异常';
                return false;
        }

        // 判断是否存在邀请码，存在邀请码则获取推荐人ID
        $invite_id = '';
        if (isset($option['code']) && !empty($option['code'])) {
            $invite_id = $this->parseInviteCode($option['code']);
            if ($invite_id < 0) {
                $this->error = '无效的邀请码';
                return false;
            }
            // 判断邀请用户是否存在
            $inviteInfo = $this->getInfo(['id' => $invite_id]);
            if (!$inviteInfo) {
                $this->error = '邀请码指定用户不存在';
                return false;
            }
        }
        $saveField['inviter_uid'] = $invite_id;
        $saveField['status'] = UCenter::instance()->getConfig('register_user_status', 1);

        return $this->add($saveField, false, $ip, $allow);
    }

    /**
     * 修改用户信息
     *
     * @param array $option 请求参数
     * @param array $allow  数据库运行操作的字段
     * @return boolean
     */
    public function edit(array $option, array $allow): bool
    {
        $check = $this->validate()->data($option)->scope('edit')->check();
        if (!$check) {
            $this->error = $this->validate()->getError();
            return false;
        }

        // 获取用户信息
        $info = $this->getInfo(['id' => $option['id']]);
        if (!$info) {
            $this->error = '获取用户信息失败';
            return false;
        }

        // $allow = ['email', 'moble', 'nickname', 'level', 'avatar', 'sex', 'comment', 'status'];
        $save = $this->allowField($allow)->save($option, ['id' => $option['id']]);
        if (!$save) {
            $this->error = '修改用户信息失败';
            return false;
        }

        return true;
    }

    /**
     * 修改、重置密码
     *
     * @param array $option 请求参数
     * @param boolean $check_old_pwd 是否验证旧密码
     * @param boolean $is_pay_password 修改的密码是否为交易密码
     * @return boolean
     */
    public function changePassword(array $option, bool $check_old_pwd = true, bool $is_pay_password = false): bool
    {
        $scope = $check_old_pwd ? 'password' : 'pwd';
        $check = $this->validate()->data($option)->scope($scope)->check();
        if (!$check) {
            $this->error = $this->validate()->getError();
            return false;
        }
        // 获取用户信息
        $info = $this->getInfo(['id' => $option['id']]);
        if (!$info) {
            $this->error = '获取用户信息失败';
            return false;
        }
        // 获取要修改验证的字段
        $field = $is_pay_password ? 'pay_password' : 'password';
        // 判断是否需要验证旧密码，需验证旧密码时，需要多传oldpwd参数
        if ($check_old_pwd && ($info[$field] != $this->encodePassword($option['old_password'], $info['salt']))) {
            $this->error = '旧密码错误';
            return false;
        }
        // 重新生成密码
        $password = $this->encodePassword($option['password'], $info['salt']);
        // 校验密码是否重复
        if ($password == $info['password'] || $password == $info['pay_password']) {
            $this->error = '密码不能重复';
            return false;
        }

        // 修改密码
        $save = $this->save([$field => $password], ['id' => $option['id']]);
        if (!$save) {
            $this->error = '修改密码失败';
            return false;
        }

        return true;
    }

    /**
     * 更改绑定邮箱、手机号
     *
     * @param integer $id 用户ID
     * @param string $account 账号
     * @param integer $type 1手机号 2邮箱
     * @return boolean
     */
    public function changeBindAccount(int $id, string $account, int $type = 1): bool
    {
        switch ($type) {
            case '1':
                if (!check('moble', $account)) {
                    $this->error = '请输入合法的手机号';
                    return false;
                }
                $field = 'moble';
                break;
            case '2':
                if (!check('email', $account)) {
                    $this->error = '请输入合法的邮箱地址';
                    return false;
                }
                $field = 'email';
                break;
            default:
                $this->error = '未支持的绑定账号类型';
                return false;
        }
        // 判断是否已使用
        $info = $this->where($field, $account)->where('id', '<>', $id)->find();
        if ($info) {
            $this->error = '更改的绑定信息其他账号已使用';
            return false;
        }

        // 修改绑定信息
        $save = $this->save([$field => $account], ['id' => $id]);
        if (!$save) {
            $this->error = '更改绑定信息失败';
            return false;
        }

        return true;
    }

    /**
     * 修改用户状态
     *
     * @param array $option 请求参数
     * @return boolean
     */
    public function changeStatus(array $option): bool
    {
        $check = $this->validate()->data($option)->scope('status')->check();
        if (!$check) {
            $this->error = $this->validate()->getError();
            return false;
        }
        // 获取用户信息
        $info = $this->getInfo(['id' => $option['idx']]);
        if (!$info) {
            $this->error = '获取用户信息失败';
            return false;
        }
        if ($option['status'] == $info['status']) {
            $this->error = '用户已修改到指定状态，请勿重复操作';
            return false;
        }

        // 修改状态
        $save = $this->save(['status' => $option['status']], ['id' => $option['idx']]);
        if (!$save) {
            $this->error = '修改用户状态失败';
            return false;
        }

        return true;
    }

    /**
     * 混淆加密密码
     *
     * @param string $password  密码
     * @param string $salt      加密盐
     * @return string
     */
    public function encodePassword(string $password, string $salt): string
    {
        return md5($password . $salt);
    }

    /**
     * 混淆加密生成登录的token
     *
     * @param string $value 加密的用户唯一值
     * @param string $ip ip地址
     * @return string
     */
    public function encodeLoginToken(string $value, string $ip = ''): string
    {
        return md5(Common::instance()->randString(8, 5) . $ip . time() . $value);
    }

    /**
     * 解析邀请码获取用户ID
     *
     * @param string $code 邀请码
     * @return integer
     */
    public function parseInviteCode(string $code): int
    {
        $uid = IdCode::instance()->code2id($code);
        return $uid - UCenter::instance()->getConfig('inviter_code', 0);
    }

    /**
     * 获取用户ID转换的邀请码
     *
     * @param integer $id  用户ID
     * @return string
     */
    public function getInviteCode(int $id): string
    {
        $uid = intval($id) + UCenter::instance()->getConfig('inviter_code', 0);
        return IdCode::instance()->id2code($uid);
    }

    /**
     * 查询用户列表场景
     *
     * @param mon\orm\db\Query $query
     * @param array $args
     * @return mon\orm\db\Query
     */
    protected function scopeList($query, $args)
    {
        $field = ['user.*', 'real.auth_type', 'real.auth_status', 'real.auth_time'];
        $query->alias('user')->join(UserRealnameAuthModel::instance()->getTable() . ' real', 'user.id = real.uid', 'left')->field($field);

        // ID搜索
        if (isset($args['id']) && check('int', $args['id'])) {
            $query->where('user.id', intval($args['id']));
        }
        // status搜索
        if (isset($args['status']) && check('int', $args['status'])) {
            $query->where('user.status', intval($args['status']));
        }
        // 按注册类型
        if (isset($args['register_type']) && check('int', $args['register_type'])) {
            $query->where('user.register_type', intval($args['register_type']));
        }
        // 按等级类型
        if (isset($args['level']) && check('int', $args['level'])) {
            $query->where('user.level', intval($args['level']));
        }
        // 按邮箱
        if (isset($args['email']) && is_string($args['email']) && !empty($args['email'])) {
            $query->where('user.email', trim($args['email']));
        }
        // 按手机号
        if (isset($args['moble']) && is_string($args['moble']) && !empty($args['moble'])) {
            $query->where('user.moble', trim($args['moble']));
        }
        // 创建时间搜索
        if (isset($args['add_start_time']) && check('int', $args['add_start_time'])) {
            $query->where('user.create_time', '>=', intval($args['start_time']));
        }
        if (isset($args['add_end_time']) && check('int', $args['add_end_time'])) {
            $query->where('user.create_time', '<=', intval($args['end_time']));
        }
        // 登录时间搜索
        if (isset($args['login_start_time']) && check('int', $args['login_start_time'])) {
            $query->where('user.login_time', '>=', intval($args['login_start_time']));
        }
        if (isset($args['login_end_time']) && check('int', $args['login_end_time'])) {
            $query->where('user.login_time', '<=', intval($args['login_end_time']));
        }
        // 排序
        if (isset($args['order']) && isset($args['sort']) && in_array($args['order'], ['login_time', 'create_time', 'update_time']) && in_array($args['sort'], ['asc', 'desc'])) {
            $query->order('user.' . $args['order'], $args['sort']);
        } else {
            $query->order('user.id', 'desc');
        }

        return $query;
    }

    /**
     * 验证字段数据库唯一
     *
     * @param array $option
     * @return boolean
     */
    protected function checkUniqueField(array $option, array $where = [])
    {
        $uniqueFields = UCenter::instance()->getConfig('unique_field', []);
        foreach ($uniqueFields as $field => $text) {
            if (isset($option[$field]) && !empty($option[$field])) {
                // 存在需要唯一的字段，且字段值不为空
                $info = $this->where($field, $option[$field])->field($field)->where($where)->find();
                if ($info) {
                    $this->error = $text . '已存在';
                    return false;
                }
            }
        }

        return true;
    }
}
