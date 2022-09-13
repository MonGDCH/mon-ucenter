<?php

declare(strict_types=1);

namespace mon\ucenter\model;

use mon\ucenter\UCenter;
use mon\orm\exception\DbException;
use mon\ucenter\exception\UCenterException;

/**
 * 用户实名认证模型
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class UserRealnameAuthModel extends BaseModel
{
    /**
     * 构造方法
     */
    public function __construct()
    {
        parent::__construct();
        // 定义表名
        $this->table = UCenter::instance()->getConfig('table.user_realname_auth');
    }

    /**
     * 登记实名认证
     *
     * @param array $option 审核登记参数
     * @param integer $uid  用户ID
     * @param array $allow  数据库运行操作的字段
     * @return boolean
     */
    public function record(array $option, int $uid, array $allow = []): bool
    {
        if (!isset($option['auth_type'])) {
            $this->error = '认证参数异常';
            return false;
        }
        switch ($option['auth_type']) {
            case '0':
                $scope = 'userRealName';
                break;
            case '1':
                $scope = 'comRealName';
                break;
            default:
                $this->error = '未知认证类型';
                return false;
        }
        $check = $this->validate()->data($option)->scope($scope)->check();
        if (!$check) {
            $this->error = $this->validate()->getError();
            return false;
        }

        // 判断是否已存在记录
        $info = $this->where('uid', $uid)->find();
        if ($info) {
            $this->error = '已提交实名认证审核登记，请勿重复提交';
            return false;
        }
        // $allow = [
        //     'uid', 'auth_type', 'real_name', 'identity', 'id_card_front', 'id_card_back',
        //     'id_card_hand', 'license', 'contact_person', 'contact_mobile', 'contact_email'
        // ];

        // 保存记录
        $option['uid'] = $uid;
        $save = $this->allowField($allow)->save($option);
        if (!$save) {
            $this->error = '登记实名认证信息失败';
            return false;
        }

        return true;
    }

    /**
     * 重新提交审核
     *
     * @param array $option 审核登记参数
     * @param integer $uid  用户ID
     * @throws UCenterException
     * @return boolean
     */
    public function restore(array $option, int $uid): bool
    {
        // 判断是否已存在记录
        $info = $this->where('uid', $uid)->find();
        if (!$info) {
            $this->error = '未提交实名认证审核登记';
            return false;
        }
        if ($info['auth_status'] != '2') {
            $this->error = '实名认证已通过或正在审核中，请勿重复提交';
            return false;
        }

        $this->startTrans();
        try {
            $del = $this->where('uid', $uid)->delete();
            if (!$del) {
                $this->rollback();
                $this->error = '重新提交审核失败';
                return false;
            }

            $add = $this->record($option, $uid);
            if (!$add) {
                $this->rollback();
                return false;
            }

            $this->commit();
            return true;
        } catch (DbException $e) {
            $this->rollback();
            throw new UCenterException('重新提交审核异常', UCenterException::REALNAME_RESET_ERROR, $e);
        }
    }

    /**
     * 审核
     *
     * @param integer $uid 用户ID
     * @param integer $status 审核状态，1通过2未通过
     * @param string $comment 备注
     * @return boolean
     */
    public function confirm(int $uid, int $status, string $comment = ''): bool
    {
        // 判断是否已存在记录
        $info = $this->where('uid', $uid)->find();
        if (!$info) {
            $this->error = '实名认证信息不存在';
            return false;
        }

        // 处理数据
        $saveInfo = [
            'auth_status'   => $status,
            'auth_time'     => time(),
            'comment'       => $comment,
        ];
        switch ($status) {
            case '1':
                // 审核通过，原状态为0或者1
                if (!in_array($info['auth_status'], [0, 1])) {
                    $this->error = '已通过审核，请勿重复审核';
                    return false;
                }
                $saveInfo['auth_time'] = time();
                break;
            case '2':
                if ($info['auth_status'] != 0) {
                    $this->error = '已进行过审核，请勿重复审核';
                    return false;
                }
                break;
            default:
                $this->error = '未知审核状态';
                return false;
        }

        // 修改状态
        $save = $this->save($saveInfo, ['uid' => $uid]);
        if (!$save) {
            $this->error = '审核信息失败';
            return false;
        }

        return true;
    }
}
