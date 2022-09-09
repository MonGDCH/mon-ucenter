<?php

declare(strict_types=1);

namespace mon\ucenter\model;

use Exception;
use mon\orm\Model;
use mon\ucenter\Validate;
use mon\ucenter\UserCenter;

/**
 * 模型基类
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.1.0   优化代码
 */
abstract class BaseModel extends Model
{
    /**
     * 新增自动写入字段
     *
     * @var array
     */
    protected $insert = ['create_time', 'update_time'];

    /**
     * 更新自动写入字段
     *
     * @var array
     */
    protected $update = ['update_time'];

    /**
     * 验证器
     *
     * @var Validate
     */
    protected $validate = Validate::class;

    /**
     * 构造方法
     */
    public function __construct()
    {
        if (!UserCenter::instance()->isInit()) {
            throw new Exception('未初始化');
        }

        $this->config = UserCenter::instance()->getConfig('database');
    }

    /**
     * 自动完成update_time字段
     * 
     * @param mixed $val 默认值
     * @param array  $row 列值
     * @return integer
     */
    protected function setUpdateTimeAttr($val): int
    {
        return time();
    }

    /**
     * 自动完成create_time字段
     * 
     * @param mixed $val 默认值
     * @param array  $row 列值
     * @return integer
     */
    protected function setCreateTimeAttr($val): int
    {
        return time();
    }
}
