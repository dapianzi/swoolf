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
    private static $ins     = [];
    private $_conn          = null;
    private $_db            = null;

    /**
     * 创建实例
     * @param  string $confkey
     * @return \m_mgdb
     */
    static function i($conf) {
        if (!isset($conf->key)) {
            return NULL;
        }
        if ( !isset(self::$ins[$conf->key]) ) {
            $m = new MongoDBDriver($conf);
            self::$ins[$conf->key] = $m;
        }
        return self::$ins[$conf->key];
    }

    /**
     * 构造方法
     * 单例模式
     */
    private function __construct($conf) {
        $uriOptions = [
//            'readPreference' => 'secondary'
        ];
        // use auth
        if (isset($conf->authSource)) {
            $uriOptions['authMechanism'] = 'SCRAM-SHA-1';
            $uriOptions['authSource'] = $conf->authSource;
            $uriOptions['username'] = $conf->username;
            $uriOptions['password'] = $conf->password;
        }
        if (isset($conf->replSet)) {
            $uriOptions['replicaSet'] = $conf->replSet;
        }
        $this->_conn = new MongoDB\Driver\Manager($conf->url.'/'.$conf->dbname, $uriOptions);
        $this->_db   = $conf->dbname;
    }

    /**
     * 插入数据
     * @param  string $collname
     * @param  array  $documents    [["name"=>"values", ...], ...]
     * @param  array  $writeOps     ["ordered"=>boolean,"writeConcern"=>array]
     * @return \MongoDB\Driver\Cursor
     */
    function insert($collname, $documents, $writeOps = []) {
        $cmd = [
            "insert"    => $collname,
            "documents" => $documents,
        ];
        $cmd += $writeOps;
        return $this->command($cmd);
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
     * 更新数据
     * @param  string $collname
     * @param  array  $updates      [["q"=>query,"u"=>update,"upsert"=>boolean,"multi"=>boolean], ...]
     * @param  array  $writeOps     ["ordered"=>boolean,"writeConcern"=>array]
     * @return \MongoDB\Driver\Cursor
     */
    function update($collname, $updates, $writeOps = []) {
        $cmd = [
            "update"    => $collname,
            "updates"   => $updates,
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
        $cmd = new MongoDB\Driver\Command($param);
        return $this->_conn->executeCommand($this->_db, $cmd);
    }

    /**
     * 获取当前mongoDB Manager
     * @return MongoDB\Driver\Manager
     */
    function getMongoManager() {
        return $this->_conn;
    }

}