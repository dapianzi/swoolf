<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/4
 * Time: 20:12
 */

namespace Swoolf\DB;

class MongoDriver
{
    //--------------  定义变量  --------------//
    private $_conn          = null;
    private $_db            = null;

    public function __construct($conf) {
        $uriOptions = [
//            'readPreference' => 'secondary'
        ];
        // use auth
        if (isset($conf['authSource'])) {
            $uriOptions['authMechanism'] = 'SCRAM-SHA-1';
            $uriOptions['authSource'] = $conf['authSource'];
            $uriOptions['username'] = $conf['username'];
            $uriOptions['password'] = $conf['password'];
        }
        if (isset($conf['replSet'])) {
            $uriOptions['replicaSet'] = $conf['replSet'];
        }
        $this->_conn = new \MongoDB\Driver\Manager($conf['url'].'/'.$conf['dbname'], $uriOptions);
        $this->_db   = $conf['dbname'];
    }

    /**
     * 删除数据
     * @param  string $collname
     * @param  array  $deletes      [["q"=>query,"limit"=>int], ...]
     * @param  array  $writeOps     ["ordered"=>boolean,"writeConcern"=>array]
     * @return \MongoDB\Driver\Cursor
     */
    function del($collname, $deletes, $writeOps = []) {
        foreach($deletes as &$_){
            if(isset($_["q"]) && !$_["q"]){
                $_["q"] = (Object)[];
            }
            if(isset($_["limit"]) && !$_["limit"]){
                $_["limit"] = 0;
            }
        }
        $cmd = [
            "delete"    => $collname,
            "deletes"   => $deletes,
        ];
        $cmd += $writeOps;
        return $this->command($cmd);
    }

    /**
     * 查询
     * @param  string $collname
     * @param  array  $filter     [query]     参数详情请参见文档。
     * @return \MongoDB\Driver\Cursor
     */
    function query($collname, $filter, $writeOps = []){
        $cmd = [
            "find"      => $collname
        ];
        if (!empty($filter)) {
            $cmd['filter'] = $filter;
        }
        $cmd += $writeOps;
        return $this->command($cmd);
    }

    /**
     * 执行MongoDB命令
     * @param array $param
     * @return \MongoDB\Driver\Cursor
     */
    function command(array $param) {
        $cmd = new \MongoDB\Driver\Command($param);
        return $this->_conn->executeCommand($this->_db, $cmd);
    }

    /**
     * 获取当前mongoDB Manager
     * @return MongoDB\Driver\Manager
     */
    function getMongoManager() {
        return $this->_conn;
    }

    public function objectId($val) {
        return new \MongoDB\BSON\ObjectId($val);
    }

    public function timeToObjectId($time) {
        return $this->objectId(str_pad(dechex($time), 8, '0', STR_PAD_LEFT) . '0000000000000000');
    }

    public function dateToObjectId($time) {
        return $this->timeToObjectId(strtotime($time));
    }

    public function count($collection, $filter=[]) {
        $params = [
            'count' => $collection,
            'query' => $filter,
        ];
        $res = $this->command($params)->toArray();
        if ($res[0]->ok) {
            return $res[0]->n;
        } else {
            return 0;
        }
    }

    public function aggregateCount($collection, $filter=null) {
        $params = [
            'aggregate' => $collection,
            'pipeline' => [
                ['$match' => empty($filter) ? new stdClass() : $filter],
                ['$count' => 'n'],
            ],
            'cursor' => new stdClass()
        ];
        $res = $this->command($params)->toArray();
        if ($res) {
            return $res[0]->n;
        } else {
            return 0;
        }
    }

    public function findOne($collection, $filter, $options=[]) {
        $options = array_merge(['limit' => 1], $options);
        $res = $this->query($collection, $filter, $options)->toArray();
        if ($res) {
            return $res[0];
        } else {
            return FALSE;
        }
    }

    public function find($collection, $filter, $options=[]) {
        return $this->query($collection, $filter, $options);
    }

    public function insert($collection, $documents, $writeOps=[]) {
        $cmd = [
            "insert"    => $collection,
            "documents" => $documents,
        ];
        $cmd += $writeOps;
        $res = $this->command($cmd)->toArray()[0];
        if ($res->n == 0 && isset($res->writeErrors)) {
            $this->errmsg = $res->writeErrors[0]->errmsg;
            return FALSE;
        }
        return $res->n;
    }

    public function insertMany($collection, $documents, $options=[]) {
        return $this->insert($collection, $documents, $options);
    }

    public function insertOne($collection, $document, $options=[]) {
        return $this->insert($collection, [$document], $options);
    }

    public function update($collname, array $updates, array $writeOps = []) {
        $cmd = [
            "update"    => $collname,
            "updates"   => $updates,
        ];
        $cmd += $writeOps;
        $res = $this->command($cmd)->toArray()[0];
        if ($res->n == 0 && isset($res->writeErrors)) {
            $this->errmsg = $res->writeErrors[0]->errmsg;
        }
        return $res->n;
    }

    public function updateMany($collection, $query, $set, $options=[]) {
        $updates = [
            ["q" => $query, "u" => $set, "upsert" => TRUE, "multi" => TRUE]
        ];
        return $this->update($collection, $updates, $options);
    }

    public function updateOne($collection, $query, $set, $options = []) {
        $updates = [
            ["q" => $query, "u" => $set, "upsert" => TRUE, "multi" => FALSE]
        ];
        return $this->update($collection, $updates, $options);
    }

    public function delete($collection, array $deletes, array $writeOps = []) {
        $res = $this->del($collection, $deletes, $writeOps)->toArray()[0];
        if ($res->n == 0 && isset($res->writeErrors)) {
            $this->errmsg = $res->writeErrors[0]->errmsg;
        }
        return $res->n;
    }

    public function deleteOne($collection, $query, array $writeOps = []) {
        $deletes = [
            ["q" => $query, "limit" => 1] // limit 不是删除的条数？？
        ];
        return $this->delete($collection, $deletes, $writeOps);
    }

    public function deleteMany($collection, $query, array $writeOps = []) {
        $deletes = [
            ["q" => $query, "limit" => 0] // limit 不是删除的条数？？
        ];
        return $this->delete($collection, $deletes, $writeOps);
    }

    public function aggregate($collection, $options) {
        $cmd = [
            'aggregate' => $collection,
            'pipeline' => [],
            'cursor' => new stdClass()
        ];
        if (isset($options['match']) && !empty($options['match'])) {
            $cmd['pipeline'][] = ['$match' => $options['match']];
        }
        if (isset($options['group']) && !empty($options['group'])) {
            $cmd['pipeline'][] = ['$limit' => $options['limit']];
        }
        if (isset($options['project']) && !empty($options['project'])) {
            $tmp = [];
            foreach ($options['project'] as $p) {
                $tmp[$p] = 1;
            }
            $cmd['pipeline'][] = ['$project' => $tmp];
        }
        if (isset($options['sort']) && !empty($options['sort'])) {
            $cmd['pipeline'][] = ['$sort' => $options['sort']];
        }
        if (isset($options['skip']) && !empty($options['skip'])) {
            $cmd['pipeline'][] = ['$skip' => $options['skip']];
        }
        if (isset($options['limit']) && !empty($options['limit'])) {
            $cmd['pipeline'][] = ['$limit' => $options['limit']];
        }
        return $this->_mongo->command($cmd);
    }


}