<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/4
 * Time: 18:22
 */
namespace Swoolf\DB;

class Mysql
{
    static public $instance;

    private $db;
    private $lastSql;
//    private $lastInsertId;
    private $errMessage;

    public function __construct($conf) {
        $this->conf = $conf;
        $this->db = new \Swoole\Coroutine\MySQL();
        $this->connect();
    }

    public function connect() {
        $this->db->connect($this->conf);
    }

    public function execute($sql, $param = array()) {
        $stmt = $this->db->prepare($sql);
        if ($stmt === false) {
            var_dump($this->db->errno, $this->db->error);
            return false;
        } else {
            $ret = $stmt->execute($param);
            $this->lastSql = $ret->queryString;
            var_dump($stmt);
            var_dump($ret);
            return $ret;
        }
    }

    public function insert($table, $columns) {
        $sql = " INSERT INTO {$table} (`" . implode ('`, `', array_keys ($columns));
        $sql .= '`) VALUES (' . $this->questionMarks (count ($columns)) . ')';
        // Now the query should be as follows:
        // INSERT INTO table (c1, c2, c3) VALUES (?, ?, ?)
        $res = $this->execute($sql, array_values($columns));
        if ($res) {
            return $this->db->last_id;
        } else {
            return FALSE;
        }
    }

    public function update($table, $param, $where, $conjunction = 'AND') {
        if (!count($param)) {
            $this->errMessage = 'update must have set.';
            throw new \Exception('update must have set.');
        }
        if (!count($where)) {
            $this->errMessage = 'update must have where.';
            throw new \Exception('update must have where.');
        }
        $whereValues = array();
        $sql = " UPDATE $table SET " . $this->makeSetSQL($param) . ' WHERE ' . $this->makeWhereSQL($where, $conjunction, $whereValues);
        return $this->execute($sql, array_merge (array_values ($param), $whereValues))->rowCount();
    }

    public function delete($table, $where, $conjunction = 'AND') {
        if (!count($where)) {
            $this->errMessage = 'delete must have where.';
            throw new \Exception('delete must have where.');
        }
        $whereValues = array();
        $sql = " DELETE FROM $table WHERE " . $this->makeWhereSQL($where, $conjunction, $whereValues);
        return $this->execute ($sql, $whereValues)->rowCount();
    }

//    /**
//     * 开启事务
//     * @return bool
//     */
//    public function begin() {
//        return $this->db->beginTransaction();
//    }
//
//    /**
//     * 事务提交
//     * @return bool
//     */
//    public function commit() {
//        return $this->db->commit();
//    }
//
//    /**
//     * 事务回滚
//     * @return bool
//     */
//    public function rollBack() {
//        return $this->db->rollBack();
//    }

    public function getColumn($sql, $param = array(), $col = 0) {
        return $this->execute($sql, $param)->fetchColumn($col);
    }

    public function getKeyValue($sql, $param = array()) {
        return $this->execute($sql, $param)->fetchAll(\PDO::FETCH_KEY_PAIR);
    }

    public function getCount($sql, $param = array()) {
        return $this->execute($sql, $param)->rowCount();
    }

    public function getAll($sql, $param = array()) {
        return $this->execute($sql, $param)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getRow($sql, $param = array()) {
        return $this->execute($sql, $param)->fetch(\PDO::FETCH_ASSOC);
    }

    public function makeSetSQL($columns) {
        if (! count ($columns)) {
            throw new \Exception ('columns must not be empty');
        }
        $tmp = array();
        // Same syntax works for NULL as well.
        foreach ($columns as $col => $val) {
            $tmp[] = "`${col}`=?";
        }
        return implode (', ', $tmp);
    }

    public function makeWhereSQL ($where_columns, $conjunction='AND', &$params = array()) {
        if (! in_array (strtoupper ($conjunction), array ('AND', '&&', 'OR', '||', 'XOR'))) {
            throw new \Exception ('conjunction'. $conjunction. 'invalid operator');
        }
        if (! count ($where_columns)) {
            return '1';
        }
        $tmp = array();
        foreach ($where_columns as $colName => $colValue)
            if ($colValue === NULL) {
                $tmp[] = "$colName IS NULL";
            } else if (is_array ($colValue)) {
                if (empty($colValue)) {
                    $tmp[] = '1=0';
                } else {
                    // Suppress any string keys to keep array_merge() from overwriting.
                    $params = array_merge ($params, array_values ($colValue));
                    $tmp[] = sprintf ('%s IN(%s)', $colName, $this->questionMarks (count ($colValue)));
                }
            }
            else
            {
                $tmp[] = "${colName}=?";
                $params[] = $colValue;
            }
        return implode (" ${conjunction} ", $tmp);
    }

    public function makeOrderBy($orders) {
        if (empty($orders)) {
            return '';
        }
        $ret = ' ORDER BY ';
        foreach ($orders as $f=>$a) {
            $ret.= $f.' '.($a==1 || $a == 'asc' ? 'ASC' : 'DESC').',';
        }
        return substr($ret, 0, -1);
    }

    public function questionMarks($count) {
        if ($count <= 0) {
            throw new \Exception('count must be greater than zero');
        }
        return implode(', ', array_fill(0, $count, '?'));
    }

    public function getLastSQL() {
        return $this->lastSql;
    }

    public function getLastInsertId() {
        return $this->db->last_id;
    }

    public function getError() {
        return $this->errMessage;
    }

}