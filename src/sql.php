<?php
/**
 * @Author: isglory
 * @E-mail: admin@ubphp.com
 * @Date:   2016-08-26 15:05:16
 * @Last Modified by:   else
 * @Last Modified time: 2018-01-11 11:31:45
 * Copyright (c) 2014-2016, UBPHP All Rights Reserved.
 */
namespace this7\sql;

class sql {

    protected $app;

    public function __construct($app = '') {
        $this->app = $app;
    }

    /**
     * @return object 数据链接对象
     */
    public function connect() {
        $class = __NAMESPACE__ . '\connection\\' . C('sql', 'driver');
        return new $class();
    }

    /**
     * @param $method 方法名
     * @param $params 对应参数
     *
     * @return mixed
     */
    public function __call($method, $params) {
        return call_user_func_array([$this->connect(), $method], $params);
    }
}