<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/4
 * Time: 18:22
 */
namespace Swoolf\DB;

class PDODriver
{
    static public $instance;

    private $dbLink;
    private $lastSql;
    private $dsn;
    private $username;
    private $password;
    private $errMessage;

    public function __construct($dsn, $username, $password) {
        $this->dsn = $dsn;
        $this->username = $username;
        $this->password = $password;
        $this->connect();
    }

    public function connect() {
        $opts = array (
            \PDO::ATTR_ERRMODE  => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_PERSISTENT  => TRUE,
            \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES "utf8", @@SQL_MODE = REPLACE(@@SQL_MODE, "NO_ZERO_DATE", "")',
            \PDO::ATTR_STRINGIFY_FETCHES => FALSE,
            \PDO::ATTR_EMULATE_PREPARES => FALSE,
        );
        $this->dbLink = new \PDO ($this->dsn, $this->username, $this->password, $opts);
    }

    public function execute($sql, $param = array()) {
        try {
            $pre = $this->dbLink->prepare($sql);
            $pre->execute($param);
        } catch (\PDOException $e) {
            if ($e->errorInfo[1] == 2006 || $e->errorInfo[1] == 2013) {
                $this->connect();
                $pre = $this->dbLink->prepare($sql);
                $pre->execute($param);
                unset($e);
            } else {
                throw $e;
            }
        }
        $this->lastSql = $pre->queryString;
        return $pre;
    }

    public function insert($table, $columns) {
        $sql = " INSERT INTO {$table} (`" . implode ('`, `', array_keys ($columns));
        $sql .= '`) VALUES (' . $this->questionMarks (count ($columns)) . ')';
        $res = $this->execute($sql, array_values($columns))->rowCount();
        if ($res > 0) {
            return $this->dbLink->lastInsertId();
        } else {
            return FALSE;
        }
    }

    public function update($table, $param, $where, $conjunction = 'AND') {
        if (!count($param)) {
            throw new \Exception('update must have set.');
        }
        if (!count($where)) {
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

    /**
     * 开启事务
     * @return bool
     */
    public function begin() {
        return $this->dbLink->beginTransaction();
    }

    /**
     * 事务提交
     * @return bool
     */
    public function commit() {
        return $this->dbLink->commit();
    }

    /**
     * 事务回滚
     * @return bool
     */
    public function rollBack() {
        return $this->dbLink->rollBack();
    }

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

    public function get($id) {
        return $this->getRow("SELECT * FROM {$this->table} WHERE {$this->pk}=?", array($id));
    }

    /**
     * @param string $col
     * @param string $table
     * 查询表的字段
     * @return mixed
     */
    public function getColInfo($table, $col) {
        return $this->getRow("SHOW COLUMNS FROM {$table} WHERE FIELD = ?", array($col));
    }

    /**
     * @param string $col
     * @param string $table
     * 快速获取枚举类型列表
     * @return array
     */
    public function getColEnum($table, $col) {
        $col_info = $this->getColInfo($table, $col);
        $enum = explode(',', preg_replace('/^enum\((.*)\)$/i', '$1', $col_info['Type']));
        return array_map(function($v){return trim($v, '\'');}, $enum);
    }

    public function upsert($table, $keys, $data, $pk='id') {
        $sql = 'SELECT '.$pk.' FROM '.$table.' WHERE '.$this->makeWhereSQL($keys, 'AND', $params);
        $id = $this->getColumn($sql, $params);
        if ($id > 0) {
            return $this->update($table, $data, [$pk => $id]);
        } else {
            return $this->insert($table, array_merge($keys, $data));
        }
    }

    public function setDec($table, $where, $field, $num=1) {
        return $this->setInc($table, $where, $field, -$num);
    }

    public function setInc($table, $where, $field, $num=1) {
        $num = intval($num);
        $params = [];
        $sql = 'UPDATE '.$table.' SET '.$field.'='.$field.'+'.$num.' WHERE '. $this->makeWhereSQL($where, 'AND', $params);
        return $this->execute($sql, $params)->rowCount();
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
        return $this->dbLink->lastInsertId();
    }

    public function getError() {
        return $this->errMessage;
    }

}