<?php
/**
 * @Author: isglory
 * @E-mail: admin@ubphp.com
 * @Date:   2016-09-08 14:07:46
 * @Last Modified by:   else
 * @Last Modified time: 2018-01-11 11:13:51
 * Copyright (c) 2014-2016, UBPHP All Rights Reserved.
 */
namespace this7\sql\connection;

class mysql extends connection {
    public function getDns() {
        return $dns = 'mysql:host=' . $this->config['host'] . ';dbname=' . $this->config['table'];
    }

    /**
     * 获取数据库表字段
     * @return array 数据表字段
     */
    public function getTableField() {
        #不是全表名是添加表前缀
        if (!empty($this->fields)) {
            return $this->fields;
        }
        $name = C('sql', "table") . '.' . $this->table;
        #字段缓存
        if (!DEBUG && F($name, '[get]', 'temp/field')) {
            $data = F($name, '[get]', 'temp/field');
        } else {
            $sql = "show columns from " . $this->table;
            if (!$result = $this->query($sql)) {
                return FALSE;
            }
            $data = [];
            foreach ($result as $res) {
                $f['field']          = $res['Field'];
                $f['type']           = $res['Type'];
                $f['null']           = $res['Null'];
                $f['key']            = ($res['Key'] == "PRI" && $res['Extra']) || $res['Key'] == "PRI";
                $f['default']        = $res['Default'];
                $f['extra']          = $res['Extra'];
                $data[$res['Field']] = $f;
            }
            DEBUG || F($name, $data, 'temp/field');
        }
        return $this->fields = $data;
    }

    /**
     * 获取表主键信息
     * @return array 返回主键数组
     */
    public function getPrimaryKey() {
        $fields = $this->getTableField();
        foreach ($fields as $v) {
            if ($v['key'] == 1) {
                return $v['field'];
            }
        }
    }

    /**
     * 获取表字段列表
     *
     * @param string $table 表名
     *
     * @return array
     * @throws \Exception
     */
    public function getTableFieldLists($table) {
        return $this->query("DESC " . $this->getPrefix() . $table);
    }

    /**
     * 判断字段是否存在
     * @param  字段
     * @param  表名
     * @return  Boolean
     */
    public function fieldExists($field, $table) {
        $fieldLists = $this->query("DESC " . $this->getPrefix() . $table);
        foreach ($fieldLists as $f) {
            if (strtolower($f['Field']) == strtolower($field)) {
                return TRUE;
            }
        }
        return FALSE;
    }

    /**
     * 判断表名是否存在
     * @param  表名
     * @return Boolean
     */
    public function tableExists($tableName) {
        $tables = $this->query("SHOW TABLES");
        foreach ($tables as $k => $table) {
            $key = 'Tables_in_' . $this->config['table'];
            if (strtolower($table[$key]) == strtolower($this->getPrefix() . $tableName)) {
                return TRUE;
            }
        }
        return FALSE;
    }

    /**
     * 获取所有表信息
     * @return array
     */
    public function getAllTableInfo() {
        $info = $this->query("SHOW TABLE STATUS FROM " . $this->config['table']);
        $arr  = [];
        foreach ($info as $k => $t) {
            $arr['table'][$t['Name']]['tablename'] = $t['Name'];
            $arr['table'][$t['Name']]['engine']    = $t['Engine'];
            $arr['table'][$t['Name']]['rows']      = $t['Rows'];
            $arr['table'][$t['Name']]['collation'] = $t['Collation'];
            $charset                               = $arr['table'][$t['Name']]['collation']                               = $t['Collation'];
            $charset                               = explode("_", $charset);
            $arr['table'][$t['Name']]['charset']   = $charset[0];
            $arr['table'][$t['Name']]['dataFree']  = $t['Data_free']; //碎片大小
            $arr['table'][$t['Name']]['indexSize'] = $t['Index_length']; //索引大小
            $arr['table'][$t['Name']]['dataSize']  = $t['Data_length']; //数据大小
            $arr['table'][$t['Name']]['totalSize'] = $t['Data_free'] + $t['Data_length'] + $t['Index_length'];
        }
        return $arr;
    }

    /**
     * 获取表大小
     * @param  表名
     * @return number
     */
    public function getTableSize($table) {
        $table = $this->getPrefix() . $table;
        $sql   = "show table status from " . $this->config['table'];
        $data  = $this->query($sql);
        foreach ($data as $v) {
            if ($v['Name'] == $table) {
                return $v['Data_length'] + $v['Index_length'];
            }
        }
        return 0;
    }

    /**
     * 修正表
     * @param  表名
     * @return [type]
     */
    public function repair($table) {
        return $this->execute("REPAIR TABLE `" . $this->getPrefix() . $table . "`");
    }

    /**
     * @param  [type]
     * @return [type]
     */
    public function optimize($table) {
        return $this->execute("OPTIMIZE TABLE `" . $this->getPrefix() . $table . "`");
    }

    /**
     * @return [type]
     */
    public function getDataBaseSize() {
        $sql  = "show table status from " . $this->config['table'];
        $data = $this->query($sql);
        $size = 0;
        foreach ($data as $v) {
            $size += $v['Data_length'] + $v['Data_length'] + $v['Index_length'];
        }
        return $size;
    }

    /**
     * 锁定数据表
     * @param  表名称
     * @return Boolean
     */
    public function lock($tables) {
        $lock = '';
        foreach (explode(',', $tables) as $tab) {
            $lock .= tablename(trim($tab)) . " WRITE,";
        }
        return $this->execute("LOCK TABLES " . substr($lock, 0, -1));
    }

    /**
     * 解锁数据表
     * @param  表名称
     * @return Boolean
     */
    public function unlock() {
        return $this->execute("UNLOCK TABLES");
    }

    /**
     * @return 清空数据表
     */
    public function truncate() {
        return $this->execute("truncate " . $this->table);
    }
}