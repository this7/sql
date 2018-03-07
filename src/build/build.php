<?php
/**
 * @Author: isglory
 * @E-mail: admin@ubphp.com
 * @Date:   2016-09-08 13:35:35
 * @Last Modified by:   else
 * @Last Modified time: 2018-01-11 11:13:31
 * Copyright (c) 2014-2016, UBPHP All Rights Reserved.
 */
namespace this7\sql\build;

abstract class build {

    /**
     * 数据库连接实例
     * @var [type]
     */
    protected $connection;

    /**
     * 数据表格
     * @var [type]
     */
    protected $table;

    abstract public function insert();

    abstract public function replace();

    abstract public function select();

    abstract public function update();

    abstract public function delete();

    /**
     * 查询参数
     * @var array
     */
    public $params = [];

    public function __construct($connection) {

        $this->connection = $connection;
    }

    public function getBindExpression($name) {
        return isset($this->params[$name]['expression']) ? $this->params[$name]['expression'] : [];
    }

    //绑定表达式
    public function bindExpression($name, $expression) {
        $this->params[$name]['expression'][] = $expression;
    }

    //绑定参数
    public function bindParams($name, $param) {
        $this->params[$name]['parames'][] = $param;
    }

    public function getBindParams($name) {
        return isset($this->params[$name]['parames']) ? $this->params[$name]['parames'] : [];
    }

    public function reset() {
        $this->params = [];
    }

    public function getSelectParams() {
        $params = [];
        $id     = 0;
        #查询参数
        foreach (['field', 'join', 'where', 'group', 'having', 'order', 'limit'] as $k) {
            foreach ($this->getBindParams($k) as $m) {
                $params[++$id] = $m;
            }
        }
        return $params;
    }

    public function getInsertParams() {
        $params = [];
        $id     = 0;
        #查询参数
        foreach (['field', 'values'] as $k) {
            foreach ($this->getBindParams($k) as $m) {
                $params[++$id] = $m;
            }
        }

        return $params;
    }

    public function getUpdateParams() {
        $params = [];
        $id     = 0;
        #查询参数
        foreach (['set', 'values', 'where'] as $k) {
            foreach ($this->getBindParams($k) as $m) {
                $params[++$id] = $m;
            }
        }

        return $params;
    }

    public function getDeleteParams() {
        $params = [];
        $id     = 0;
        #s查询参数
        foreach (['where'] as $k) {
            foreach ($this->getBindParams($k) as $m) {
                $params[++$id] = $m;
            }
        }

        return $params;
    }

    protected function parseTable() {
        return $this->connection->getTable();
    }

    protected function parseField() {
        $expression = $this->getBindExpression('field');

        return $expression ? implode(',', $expression) : '*';
    }

    protected function parseValues() {
        $values = [];
        foreach ($this->params['values']['expression'] as $k => $v) {
            $values[] = "?";
        }

        return implode(',', $values);
    }

    public function parseJoin() {
        $expression = $this->getBindExpression('join');
        $as         = preg_replace("/^" . $this->connection->getPrefix() . "/", '', $this->connection->getTable());
        return implode(' ', $expression);
    }

    public function parseWhere() {
        if ($expression = $this->getBindExpression('where')) {
            return "WHERE " . implode(' ', $expression);
        }
    }

    protected function parseGroupBy() {
        if ($expression = $this->getBindExpression('groupBy')) {
            return "GROUP BY " . implode(',', $expression);
        }
    }

    protected function parseHaving() {
        if ($expression = $this->getBindExpression('having')) {
            return "HAVING " . current($expression);
        }
    }

    protected function parseOrderBy() {
        if ($expression = $this->getBindExpression('orderBy')) {
            return "ORDER BY " . implode(',', $expression);
        }
    }

    protected function parseLimit() {
        if ($expression = $this->getBindExpression('limit')) {
            return "LIMIT " . current($expression);
        }
    }

    protected function parseSet() {
        if ($expression = $this->getBindExpression('set')) {
            $set = '';
            foreach ($expression as $k => $v) {
                $set .= "`{$v}`=?,";
            }

            return $set ? 'SET ' . substr($set, 0, -1) : '';
        }
    }

    protected function parseUsing() {
        if ($expression = $this->getBindExpression('using')) {
            return "USING " . implode(',', $expression);
        }
    }
}
