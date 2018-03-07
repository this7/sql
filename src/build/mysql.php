<?php
/**
 * @Author: isglory
 * @E-mail: admin@ubphp.com
 * @Date:   2016-09-08 13:41:12
 * @Last Modified by:   else
 * @Last Modified time: 2018-01-11 11:13:35
 * Copyright (c) 2014-2016, UBPHP All Rights Reserved.
 */
namespace this7\sql\build;

class mysql extends build {

    public function insert() {

        return str_replace([
            '%table%',
            '%field%',
            '%values%',
        ], [
            $this->parseTable(),
            $this->parseField(),
            $this->parseValues(),
        ], "INSERT INTO %table% (%field%) VALUES(%values%)");
    }

    public function replace() {
        return str_replace([
            '%table%',
            '%field%',
            '%values%',
        ], [
            $this->parseTable(),
            $this->parseField(),
            $this->parseValues(),
        ], "REPLACE INTO %table% (%field%) VALUES(%values%)");
    }

    public function select() {
        return str_replace([
            '%field%',
            '%table%',
            '%join%',
            '%where%',
            '%groupBy%',
            '%having%',
            '%orderBy%',
            '%limit%',
        ], [
            $this->parseField(),
            $this->parseTable(),
            $this->parseJoin(),
            $this->parseWhere(),
            $this->parseGroupBy(),
            $this->parseHaving(),
            $this->parseOrderBy(),
            $this->parseLimit(),
        ], 'SELECT %field% FROM %table% %join% %where% %groupBy% %having% %orderBy% %limit%');
    }

    public function update() {
        return str_replace([
            '%table%',
            '%set%',
            '%where%',
        ], [
            $this->parseTable(),
            $this->parseSet(),
            $this->parseWhere(),
        ], "UPDATE %table% %set% %where%");
    }

    public function delete() {
        return str_replace([
            '%table%',
            '%using%',
            '%where%',
        ], [
            $this->parseTable(),
            $this->parseUsing(),
            $this->parseWhere(),
        ], "DELETE FROM %table% %using% %where%");
    }
}