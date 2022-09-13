<?php

declare(strict_types=1);

namespace mon\ucenter\exception;

use Exception;

/**
 * 用户中心异常
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class UCenterException extends Exception
{
    /**
     * 登录异常
     */
    const LOGIN_ERROR = 1001;

    /**
     * 重新提交审核异常
     */
    const REALNAME_RESET_ERROR = 2002;
}
