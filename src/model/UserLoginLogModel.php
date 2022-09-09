<?php

declare(strict_types=1);

namespace mon\ucenter\model;

use mon\util\Instance;
use mon\ucenter\UserCenter;

/**
 * 用户登录日志模型
 *
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class UserLoginLogModel extends BaseModel
{
    use Instance;

    /**
     * 构造方法
     */
    public function __construct()
    {
        parent::__construct();
        // 定义表名
        $this->table = UserCenter::instance()->getConfig('table.user_login_log');
    }

    /**
     * 验证登录账号是否已超过登录错误次数限制
     *
     * @param integer $uid 用户ID
     * @return boolean
     */
    public function checkDisableAccount(int $uid): bool
    {
        $config = UserCenter::instance()->getConfig('login_faild');
        $start_time = time() - (60 * $config['login_gap']);
        $count = $this->where('create_time', '>=', $start_time)->where('uid', $uid)->where('type', '>', 1)
            ->order('id', 'desc')->limit($config['account_error_limit'])->count();

        if ($count >= $config['account_error_limit']) {
            // 达到错误次数限制，且最后一次错误的时间在冻结时间内，账号冻结登录
            $this->error = "已连续错误登录{$config['account_error_limit']}次，请{$config['login_gap']}分钟后再登录";
            return false;
        }

        return true;
    }

    /**
     * 验证IP是否禁止登陆
     *
     * @param string $ip
     * @return boolean
     */
    public function checkDisableIP(string $ip): bool
    {
        $config = UserCenter::instance()->getConfig('login_faild');
        $start_time = time() - ($config['login_gap'] * 60);
        $count = $this->where('create_time', '>=', $start_time)->where('ip', $ip)->where('type', '>', 1)
            ->order('id', 'desc')->limit($config['ip_error_limit'])->count();

        if ($count >= $config['ip_error_limit']) {
            $this->error = '异常登录操作，请稍后再登录!';
            return false;
        }

        return true;
    }

    /**
     * 记录日志
     *
     * @param array $option     请求参数
     * @return boolean
     */
    public function record(array $option): bool
    {
        $check = $this->validate()->rule([
            'uid'       => ['required', 'int', 'min:0'],
            'type'      => ['required', 'int', 'min:0'],
            'action'    => ['required', 'str'],
            'content'   => ['str'],
            'ua'        => ['str'],
            'ip'        => ['str'],
        ])->message([
            'uid'       => '请输入用户ID',
            'type'      => '请输入合法的记录类型',
            'action'    => '请输入操作类型',
            'content'   => '请输入操作内容',
            'ua'        => '请输入合法的user-agent',
            'ip'        => '请输入合法的IP地址'
        ])->data($option)->check();
        if (!$check) {
            $this->error = $this->validate()->getError();
            return false;
        }

        $info = [];
        $info['type'] = $option['type'];
        $info['action'] = $option['action'];
        if (isset($option['uid'])) {
            $info['uid'] = $option['uid'];
        }
        if (isset($option['content']) && !empty($option['content'])) {
            $info['content'] = $option['content'];
        }

        $saveLogID = $this->save($info, null, 'id');
        if (!$saveLogID) {
            $this->error = '记录登录日志失败';
            return false;
        }

        return true;
    }

    /**
     * 查询日志列表
     *
     * @param array $option 请求参数
     * @return array
     */
    public function queryList(array $option): array
    {
        $limit = isset($option['limit']) ? intval($option['limit']) : 10;
        $page = isset($option['page']) ? intval($option['page']) : 1;

        // 查询
        $list = $this->scope('list', $option)->page($page, $limit)->order('id', 'DESC')->select();
        $total = $this->scope('list', $option)->count();

        return [
            'list'      => $list,
            'total'     => $total,
            'pageSize'  => $limit,
            'page'      => $page
        ];
    }

    /**
     * 查询列表场景
     *
     * @param mixed $query
     * @param mixed $args
     * @return mixed
     */
    protected function scopeList($query, $args)
    {
        $field = ['log.*', 'user.nickname', 'user.email', 'user.moble'];
        $query->alias('log')->join(UserModel::instance()->getTable() . ' user', 'log.uid=user.id', 'left')->field($field);
        // 按邮箱
        if (isset($args['email']) && is_string($args['email']) && !empty($args['email'])) {
            $query->whereLike('user.email', trim($args['email']));
        }
        // 按手机号
        if (isset($args['moble']) && is_string($args['moble']) && !empty($args['moble'])) {
            $query->whereLike('user.moble', trim($args['moble']));
        }
        // 时间搜索
        if (isset($args['start_time']) && is_numeric($args['start_time']) && is_int($args['start_time'] + 0) && $args['start_time'] != '') {
            $query->where('log.create_time', '>=', intval($args['start_time']));
        }
        if (isset($args['end_time']) && is_numeric($args['end_time']) && is_int($args['end_time'] + 0) && $args['end_time'] != '') {
            $query->where('log.create_time', '<=', intval($args['end_time']));
        }

        return $query;
    }
}
