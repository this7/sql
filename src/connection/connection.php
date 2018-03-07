<?php
/**
 * @Author: isglory
 * @E-mail: admin@ubphp.com
 * @Date:   2016-09-08 13:49:27
 * @Last Modified by:   qinuoyun
 * @Last Modified time: 2018-02-04 22:38:00
 * Copyright (c) 2014-2016, UBPHP All Rights Reserved.
 */
namespace this7\sql\connection;
use Closure;
use Exception;
use PDO;
use this7\sql\bin\query;

abstract class connection {

    //查询语句日志
    protected static $queryLog = [];
    //查询实例
    protected $query;
    //模型类
    public $model;
    //数据库配置
    protected $config;
    //数据
    protected $data;
    //表名
    protected $table;
    //字段列表
    protected $fields;
    //本次查询数据库连接
    protected $link;
    //本次查询影响的条数
    protected $affectedRow;

    //获取连接dns字符串
    abstract public function getDns();

    //检测字段是否存在
    abstract public function fieldExists($fieldName, $table);

    //检测表是否存在
    abstract public function tableExists($tableName);

    //获取所有表信息
    abstract public function getAllTableInfo();

    //获取表大小
    abstract public function getTableSize($table);

    //修复数据表
    abstract public function repair($table);

    //优化表碎片
    abstract public function optimize($table);

    //获得数据库大小
    abstract public function getDataBaseSize();

    //获取表主键
    abstract public function getPrimaryKey();

    private $container = array();

    public function __construct() {
        #获取数据库发送
        $this->query = new query($this);
        #获取数据库链接
        $this->getLink();
    }

    public function table($tableName) {
        #模型实例时不允许改表名
        $this->table = $this->table ?: $this->config['prefix'] . $tableName;
        #缓存表字段
        $this->getTableField();
        #获取表主键
        $this->getPrimaryKey();
        return $this;
    }

    public function getTable() {
        return $this->table;
    }

    public function getPrefix() {
        return $this->config['prefix'];
    }

    //设置模型
    public function model(Model $model) {
        $this->model = $model;

        return $this->table($this->model->getTableName());
    }

    public function getModel() {
        return $this->model;
    }

    public function data($data) {
        $this->data = $data;
    }

    public function getData() {
        return $this->data;
    }

    /**
     * 获取连接
     *
     * @param bool $type true写入 false 读取
     */
    protected function getLink($type = true) {
        static $links = [];
        $this->config = C('sql');
        $name         = serialize($this->config);
        if (isset($links[$name])) {
            return $this->link = $links[$name];
        }
        $dns          = $this->getDns();
        $links[$name] = new PDO($dns, $this->config['user'], $this->config['password'], [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'"]);
        $links[$name]->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->execute("SET sql_mode = ''");
        return $this->link = $links[$name];
    }

    public function getInsertId() {
        return $this->link->lastInsertId();
    }

    /**
     * 当绑定的参数以零开始编号时,设置为以壹开始编号
     * 这样才可以使用预准备
     *
     * @param array $params
     *
     * @return array
     */
    public function setParamsSort(array $params) {
        if (is_numeric(key($params)) && key($params) == 0) {
            $tmp = [];
            foreach ($params as $key => $value) {
                $tmp[$key + 1] = $value;
            }
            $params = $tmp;
        }

        return $params;
    }

    /**
     * 执行SQL语句，不返回结果集
     *
     * @param $sql
     * @param array $params
     *
     * @return bool
     * @throws \Exception
     */
    public function execute($sql, array $params = []) {
        $this->query->build()->reset();
        #准备sql
        $sth = $this->getLink(true)->prepare($sql);
        #绑定参数
        $params = $this->setParamsSort($params);
        foreach ((array) $params as $key => $value) {
            $sth->bindParam($key, $params[$key], is_numeric($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        try {
            #执行查询
            $sth->execute();
            $this->affectedRow = $sth->rowCount();
            #记录查询日志
            if (DEBUG) {
                self::$queryLog[] = $sql . var_export($params, true);
            }
            return true;
        } catch (Exception $e) {
            if (DEBUG) {
                $error = $sth->errorInfo();
                throw new Exception($sql . " ;BindParams:" . var_export($params, true) . implode(';', $error));
            } else {
                return false;
            }
        }
    }

    /**
     * 执行SQL语句，返回结果集
     *
     * @param $sql
     * @param array $params
     *
     * @return bool
     * @throws \Exception
     */
    public function query($sql, array $params = []) {
        $this->query->build()->reset();
        #准备sql
        $sth = $this->getLink(false)->prepare($sql);
        #设置保存数据
        $sth->setFetchMode(PDO::FETCH_ASSOC);
        #绑定参数
        $params = $this->setParamsSort($params);
        foreach ((array) $params as $key => $value) {
            $sth->bindParam($key, $params[$key], is_numeric($params[$key]) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        try {
            #执行查询
            $sth->execute();
            $this->affectedRow = $sth->rowCount();
            #记录日志
            if (DEBUG) {
                self::$queryLog[] = $sql . var_export($params, true);
            }
            return $sth->fetchAll() ?: [];
        } catch (Exception $e) {
            if (DEBUG) {
                $error = $sth->errorInfo();
                throw new Exception($sql . " ;BindParams:" . var_export($params, true) . implode(';', $error));
            } else {
                return false;
            }
        }
    }

    /**
     * 获取受影响条数
     * @return number
     */
    public function getAffectedRow() {
        return $this->affectedRow;
    }

    //移除表中不存在的字段
    public function filterTableField($data) {
        $new = [];
        if (is_array($data)) {
            foreach ($data as $name => $value) {
                if (key_exists($name, $this->fields)) {
                    $new[$name] = $value;
                }
            }
        }
        return $new;
    }

    /**
     * 执行事务处理
     *
     * @param \Closure $closure
     *
     * @return $this
     */
    public function transaction(Closure $closure) {
        try {
            $this->beginTransaction();
            //执行事务
            $closure();
            $this->commit();
        } catch (Exception $e) {
            //回滚事务
            $this->rollBack();
        }

        return $this;
    }

    //开启一个事务
    public function beginTransaction() {
        $this->getLink()->beginTransaction();

        return $this;
    }

    //开启事务
    public function rollback() {
        $this->getLink()->rollback();

        return $this;
    }

    //开启事务
    public function commit() {
        $this->getLink()->commit();

        return $this;
    }

    //获得查询SQL语句
    public function getQueryLog() {
        return self::$queryLog;
    }

    public function __call($method, $arguments) {
        return call_user_func_array([$this->query, $method], $arguments);
    }

    public function __get($name) {
        return isset($this->data[$name]) ? $this->data[$name] : '';
    }
}
