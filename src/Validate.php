<?php

declare(strict_types=1);

namespace mon\ucenter;

/**
 * 用户验证器
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Validate extends \mon\util\Validate
{
    /**
     * 验证规则
     *
     * @var array
     */
    public $rule = [
        'idx'           => ['required', 'int', 'min:1'],
        'username'      => ['required', 'str', 'minLength:3', 'username'],
        'password'      => ['required', 'account', 'maxLength:16', 'minLength:6'],
        'pay_password'  => ['required', 'account', 'maxLength:16', 'minLength:6'],
        'old_password'  => ['required', 'account', 'maxLength:16', 'minLength:6'],

        'code'          => ['str', 'checkCode'],
        'email'         => ['email'],
        'moble'         => ['moble'],
        'nickname'      => ['str', 'maxLength:24'],
        'level'         => ['int', 'min:0', 'max:10'],
        // 'avatar'        => ['str', 'avatar:jpg,jpeg,png'],
        // 微信头像地址不存在后缀名
        'avatar'        => ['url'],
        'sex'           => ['int', 'in:0,1,2'],

        'status'        => ['required', 'in:0,1,2,3'],
        'comment'       => ['str', 'maxLength:200'],

        'register_type' => ['required', 'int', 'min:0'],
        'login_type'    => ['required', 'in:1,2'],

        // 实名认证
        'auth_type'     => ['required', 'in:0,1'],
        'real_name'     => ['required', 'str', 'minLength:2'],
        'identity'      => ['required', 'str', 'identity'],
        'id_card_front' => ['required', 'str', 'url'],
        'id_card_back'  => ['required', 'str', 'url'],
        'id_card_hand'  => ['required', 'str', 'url'],
        'license'       => ['required', 'str', 'url'],

        // 第三方账号
        'openid'        => ['required', 'str'],
        'platform'      => ['required', 'int', 'min:0']
    ];

    /**
     * 错误提示信息
     *
     * @var array
     */
    public $message = [
        'idx'           => '参数异常',
        'username'      => '请输入合法的用户名',
        'password'      => [
            'required'  => '密码必须',
            'maxLength' => '密码长度不能超过16',
            'minLength' => '密码长度不能小于6',
            'account'   => '密码格式错误'
        ],
        'pay_password'  => [
            'required'  => '交易密码必须',
            'maxLength' => '交易密码长度不能超过16',
            'minLength' => '交易密码长度不能小于6',
            'account'   => '交易密码格式错误'
        ],
        'old_password'  => [
            'required'  => '旧密码必须',
            'maxLength' => '旧密码长度不能超过16',
            'minLength' => '旧密码长度不能小于6',
            'account'   => '旧密码格式错误'
        ],


        'code'          => '无效的邀请码',
        'email'         => '请输入合法的邮箱地址',
        'moble'         => '请输入合法的手机号码',
        'nickname'      => '请输入合法的昵称',
        'level'         => '请指定合法的用户等级',
        'avatar'        => '头像只允许使用jpg,jpeg,png格式',
        'sex'           => '请选择合法的性别',

        'status'        => '请选择和合法的用户状态',
        'comment'       => '签名描述长度必须小于200',

        'login_type'    => '无效的登录类型',
        'register_type' => '无效的注册类型',

        // 实名认证
        'type'          => '请选择合法的认证类型',
        'real_name'     => '请输入合法的真实名称',
        'identity'      => '请输入合法的证件号码',
        'id_card_front' => '请上传填入合法的身份证正面图片地址',
        'id_card_back'  => '请上传填入合法的身份证反面图片地址',
        'id_card_hand'  => '请上传填入合法的手持身份证图片地址',
        'license'       => '请上传填入合法的营业执照图片地址',

        // 第三方平台账号
        'openid'        => 'openid参数异常',
        'platform'      => '平台参数异常'
    ];

    /**
     * 验证场景
     *
     * @var array
     */
    public $scope = [
        'pwd'           => ['idx', 'password'],
        'password'      => ['idx', 'password', 'old_password'],
        'edit'          => ['idx', 'email', 'moble', 'nickname', 'level', 'avatar', 'sex', 'comment', 'status'],
        'status'        => ['idx', 'status'],
        'register'      => ['register_type', 'username', 'password'],
        'login'         => ['username', 'password', 'login_type'],

        // 实名认证
        'userRealName'  => ['auth_type', 'real_name', 'identity', 'id_card_front', 'id_card_back', 'id_card_hand'],
        'comRealName'   => ['auth_type', 'real_name', 'identity', 'id_card_front', 'id_card_back', 'id_card_hand', 'license'],

        // 第三方平台账号
        'bind'          => ['openid', 'platform'],
        'platform'      => ['platform']
    ];

    /**
     * 验证身份证号码/营业执照号码
     *
     * @param string $val
     * @return boolean
     */
    public function identity(string $val): bool
    {
        $type = isset($this->data['type']) ? $this->data['type'] : '0';
        if ($type != '1') {
            return $this->idCard($val);
        }

        $reg = '/[^_IOZSVa-z\W]{2}\d{6}[^_IOZSVa-z\W]{10}/';
        return $this->regexp($val, $reg);
    }


    /**
     * 自定义验证头像后缀
     *
     * @param  string $val  
     * @param  string $rule 
     * @return boolean
     */
    public function avatar(string $val, string $rule): bool
    {
        // 获取文件后缀
        $img = explode(".", $val);
        $ext = $img[count($img) - 1];
        $rules = explode(",", $rule);
        return in_array($ext, $rules);
    }
}
