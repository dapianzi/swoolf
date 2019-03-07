<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/14
 * Time: 17:53
 */

namespace Swoolf;

use Throwable;

final class App
{

    protected static $INSTANCE = NULL;

    public static $facades = [];

    private $callbackFn = [];

    public $conf = NULL;

    public $db;
    public $redis;

    /*
     * dispatcher
     */
    public $dispatcher = NULL;

    /*
     * Server
     */
    const SERVER_TYPE_WS = 1;
    const SERVER_TYPE_TCP = 2;
    const SERVER_TYPE_HTTP = 3;
    public $server = NULL;
    protected $serverType = self::SERVER_TYPE_WS;
    protected $serverName = 'Swoolf-server';
    protected $serverHost = '0.0.0.0';
    protected $serverPort = 8907;
    protected $serverMode = SWOOLE_PROCESS;
    protected $serverSettings = [];

    // swoole table
    public $table;

    public function __construct($ini)
    {

        // parse ini config
        if (is_array($ini)) {
            $this->conf = $ini;
        } else {
            $this->conf = $this->parseIni($ini);
        }
        // log config
        $this->iniDebug();
        // server config
        $this->iniServerConf();
        // dispatcher config
        $this->iniDispatcher();
        // init facades
        $this->iniFacades();
        Register::set('conf', $this->conf);
        self::$INSTANCE = $this;
    }

    private function parseIni($file) {
        $conf = Utils::parseInFile($file);
        if (defined('APP_ENV') && APP_ENV == 'product') {
            return isset($conf['product']) ? $conf['product'] : $conf['common'];
        }
        return isset($conf['develop']) ? $conf['develop'] : $conf['common'];
    }

    private function iniFacades() {
        // register facades
        $this->facade('loader', __NAMESPACE__.'\Loader');
        $this->facade('log', __NAMESPACE__.'\Log');
        $this->facade('utils', __NAMESPACE__.'\Utils');
        $this->facade('event', __NAMESPACE__.'\Event');
    }

    private function iniDebug() {
        $debug = FALSE;
        $log_file = 'log.log';
        if (isset($this->conf['debug'])) {
            $debug = $this->conf['debug'];
        }
        if (isset($this->conf['log_file'])) {
            $log_file = $this->conf['log_file'];
        }
        Log::setDebug($debug);
        Log::setLogFile($log_file);
    }

    private function iniServerConf() {
        $default = [
            'worker_num' => 4,
            'user' => 'root',
            'group' => 'root',
            'daemonize' => 1,
            'log_file' => '/tmp/swoole.http.log',
            'pid_file' => '/tmp/server.http.pid',
            'log_level' => SWOOLE_LOG_DEBUG,
            'max_request' => 5,
        ];

        if (isset($this->conf['server']['type'])) {
            $this->serverType = $this->conf['server']['type'];
        }
        if (isset($this->conf['server']['name'])) {
            $this->serverName = $this->conf['server']['name'];
        }
        if (isset($this->conf['server']['host'])) {
            $this->serverHost = $this->conf['server']['host'];
        }
        if (isset($this->conf['server']['port'])) {
            $this->serverPort = $this->conf['server']['port'];
        }
        if (isset($this->conf['server']['mode'])) {
            $this->serverMode = $this->conf['server']['mode'];
        }
        if (isset($this->conf['server']['settings'])) {
            $this->serverSettings = array_merge($default, $this->conf['server']['settings']);
        }
    }

    private function iniRedis() {
        if (isset($this->conf['redis'])) {
            $redis = new DB\RedisDriver();
            $redis->connect($this->conf['redis']['host'], $this->conf['redis']['port'], $this->conf['redis']['timeout']);
//            if (isset($this->conf['redis']['options'])) {
//                $redis->setOptions($this->conf['redis']['options']);
//            }
            $this->redis = $redis;
        }
    }

    private function iniDB() {
        if (isset($this->conf['db'])) {
            if (isset($this->conf['db']['multi']) && $this->conf['db']['multi']) {
                $this->db = [];
                $dbs = explode(',', $this->conf['db']['dbs']);
                foreach ($dbs as $name) {
                    $this->db[$name] = $this->getDB($this->conf[$name]);
                }
            } else {
                $this->db = $this->getDB($this->conf['db']);
            }
        }
    }

    private function getDB($conf) {
        switch ($conf['type']){
            case 'mongo':
                $db = new DB\MongoDriver($conf);
                break;
            case 'pdo':
                $db = new DB\PDODriver($conf['dsn'], $conf['username'], $conf['password']);
                break;
            case 'mysql':
            default:
                $db = new CoDB\Mysql($conf);
        }
        return $db;
    }

    private function iniDispatcher() {
        $dispatcher = isset($this->conf['dispatcher']) ? $this->conf['dispatcher'] : '';
        $this->dispatcher = new Dispatcher($dispatcher);
    }

    private function initServer() {
        switch ($this->serverType) {
            case self::SERVER_TYPE_HTTP: {
                $this->server = new Server\Http($this->serverHost, $this->serverPort);
                break;
            }
            case self::SERVER_TYPE_WS: {
                $this->server = new Server\WebSocket($this->serverHost, $this->serverPort);
                break;
            }
            case self::SERVER_TYPE_TCP:
            default: {
                $this->server = new Server\TcpServer($this->serverHost, $this->serverPort);
                break;
            }
        }
        $this->server->set($this->serverSettings);
        $this->bind();
    }

    public function facade($name, $class) {
        self::$facades[$name] = $class;
    }

    private function bind() {
        /************ all server. *************/
        $this->server->on('start', [$this, 'onStart']);
        $this->server->on('managerStart', [$this, 'onManagerStart']);
        $this->server->on('shutdown', [$this, 'onShutdown']);
        $this->server->on('workerStart', [$this, 'onWorkerStart']);
        $this->server->on('workerStop', [$this, 'onWorkerStop']);
        $this->server->on('workerExit', [$this, 'onWorkerExit']);

        if ($this->serverType == self::SERVER_TYPE_WS) {
            /*
             * web socket server
             */
            $this->server->on('message', [$this, 'onWSMessage']);
            $this->server->on('open', [$this, 'onWSOpen']);
            $this->server->on('close', [$this, 'onWSClose']);

        } else if ($this->serverType == self::SERVER_TYPE_HTTP) {
            /*
             * http server
             */
            $this->server->on('request', [$this, 'onHttpRequest']);

        } else if ($this->serverType == self::SERVER_TYPE_TCP) {
            /*
             * tcp server
             */
            $this->server->on('receive', [$this, 'onSocketReceive']);
        }

        if ($this->serverSettings['task_worker_num']) {
            $this->server->on('task', [$this, 'onTask']);
            $this->server->on('finish', [$this, 'onFinish']);
        }
    }

    /**
     * 自定义server回调
     * @param $event
     * @param $func
     */
    public function on($event, $func) {
        $this->callbackFn[$event] = $func;
    }

    public function onStart($server) {
        Log::info($this->serverName . ' master process start [OK].');
        Log::info(sprintf('master pid [%d], manager pid [%d]', $server->master_pid, $server->manager_pid));
        swoole_set_process_name($this->serverName.'-master');
        if (isset($this->callbackFn['start'])) {
            call_user_func($this->callbackFn['start'], $server);
        }
    }

    public function onManagerStart($server) {
        Log::info($this->serverName . ' manager process start [OK].');
        swoole_set_process_name($this->serverName.'-manager');
        if (isset($this->callbackFn['managerStart'])) {
            call_user_func($this->callbackFn['managerStart'], $server);
        }
    }

    public function onShutdown($server) {
        Log::warm($this->serverName . ' shutdown. BYE~');
        if (isset($this->callbackFn['shutdown'])) {
            call_user_func($this->callbackFn['shutdown'], $server);
        }
    }

    public function onWorkerStart($server, $worker_id) {
        // init redis
        $this->iniRedis();
        $this->redis->sAdd('user:workers', $worker_id);
        // init db
        $this->iniDB();
        $this->db['mongo']->insertOne('worker', ['id' => $worker_id, 'time' => date('Y-m-d H:i:s')]);
        if ($server->taskworker) {
            Log::info(sprintf('task [%d] begin..', $worker_id));
            swoole_set_process_name($this->serverName. '-task-worker-'.$worker_id);
        } else {
            Log::info(sprintf('worker [%d] start..', $worker_id));
            swoole_set_process_name($this->serverName. '-worker-'.$worker_id);
        }
        if (isset($this->callbackFn['workerStart'])) {
            call_user_func($this->callbackFn['workerStart'], $server, $worker_id);
        }
    }

    public function onWorkerStop($server, $worker_id) {
        if ($server->taskworker) {
            Log::ok(sprintf('task [%d] finished.', $worker_id));
        } else {
            Log::warm(sprintf('worker [%d] stop.', $worker_id));
        }
        if (isset($this->callbackFn['workerStop'])) {
            call_user_func($this->callbackFn['workerStop'], $server, $worker_id);
        }
    }

    public function onWorkerExit($server, $worker_id) {
        Log::warm('Worker ['. $worker_id .'] is exiting..');
        if (isset($this->callbackFn['workerExit'])) {
            call_user_func($this->callbackFn['workerExit'], $server, $worker_id);
        }
    }

    /************************ task worker ************************/
    public function onTask($serv, $task_id, $src_worker_id, $data) {
        Log::log(sprintf('task %d from %d begin at %f.', $task_id, $src_worker_id, microtime(TRUE)));
        if (isset($this->callbackFn['task'])) {
            call_user_func($this->callbackFn['task'], $serv, $task_id, $src_worker_id, $data);
        }
    }

    public function onFinish($serv, $task_id, $data) {
        Log::log(sprintf('task %d finish at %f.', $task_id, microtime(TRUE)));
        Log::err("11111");
        if (isset($this->callbackFn['finish'])) {
            Log::err("nothing");
            call_user_func($this->callbackFn['finish'], $serv, $task_id, $data);
        }
        Log::err("2222");
    }

    /************************ http server ************************/
    public function onHttpRequest($request, $response) {
        Log::log(sprintf('request "%s" From [%d]', $request->server['request_uri'], $request->fd));
        if (isset($this->callbackFn['request'])) {
            call_user_func($this->callbackFn['request'], $request, $response);
        }
    }

    /************************ web socket ************************/
    public function onWSOpen($server, $request) {
        Log::log('Server handshake success with fd['.$request->fd.'].');
        if (isset($this->callbackFn['open'])) {
            call_user_func($this->callbackFn['open'], $server, $request);
        }
    }

    public function onWSClose($server, $fd) {
        Log::log('Client fd['.$fd.'] closed.');
        if (isset($this->callbackFn['close'])) {
            call_user_func($this->callbackFn['close'], $server, $fd);
        }
    }

    public function onWSMessage($server, $frame) {
        Log::warm("Current Worker ID: ".$server->worker_id);
//        $this->log::log("receive data from ".$frame->fd);
        if (isset($this->callbackFn['message'])) {
            call_user_func($this->callbackFn['message'], $server, $frame);
        }
    }

    /************************ tcp socket ************************/


    /**
     * 创建 swoole 共享内存 table
     * @param $name
     * @param $columns
     * @param int $length
     */
    public function createTable($name, $columns, $length=1024) {
        $table = new \Swoole\Table($length);
        foreach ($columns as $col) {
            $table->column($col['name'], $col['type'], $col['length']);       //1,2,4,8
        }
        $table->create();
        $this->table[$name] = $table;
    }

    public function getTable($name) {
        if (isset($this->table[$name])) {
            return $this->table[$name];
        }
        return null;
    }

    public function getServerPid() {
        $pid_file = $this->serverSettings['pid_file'];
        if (file_exists($pid_file)) {
            return intval(file_get_contents($pid_file));
        }
        return FALSE;
    }

    public function checkServerIsRunning() {
        $pid = $this->getServerPid();
        return $pid && $this->checkPidIsRunning($pid);
    }

    public function checkPidIsRunning($pid) {
        return \Swoole\Process::kill($pid, 0);
    }

    public function reload(){
        $pid = $this->getServerPid();
        if (!$pid) {
            Log::warm($this->serverName . ": can not find manager pid file", TRUE);
            Log::err($this->serverName . ": reload [FAIL]", TRUE);
            return false;
        } elseif (!\Swoole\Process::kill($pid, 10)) { //USR1
            Log::warm($this->serverName . ": send signal to manager failed", TRUE);
            Log::err($this->serverName . ": stop [FAIL]", TRUE);
            return false;
        }
        Log::ok($this->serverName . ": reload [OK]", TRUE);
        return true;
    }

    public function status(){
        Log::log('*****************************************************************', TRUE);
        Log::log('Summary: ', TRUE);
        Log::log('Swoole Version: ' . SWOOLE_VERSION, TRUE);
        if (!$this->checkServerIsRunning()) {
            Log::err($this->serverName . ': is running [stop]', TRUE);
            Log::log("*****************************************************************", TRUE);
            return false;
        }
        Log::ok($this->serverName . ': is running [OK]', TRUE);
        Log::log('master pid : is ' . $this->getServerPid(), TRUE);
        Log::log("*****************************************************************", TRUE);
    }

    public function setServerName($name) {
        $this->serverName = $name;
    }

    public function start() {
//        Log::ok($this->serverName . " start: [OK]", TRUE);
        $this->server->start();
//        if ($this->checkServerIsRunning()) {
//        }
    }

    public function shutdown() {
        $pid = $this->getServerPid();
        if (!\Swoole\Process::kill($pid, 15)) {
            Log::err("]" . $this->serverName . ": send signal to master failed", TRUE);
            Log::err($this->serverName . " stop: [FAIL]", TRUE);
            return false;
        }
        usleep(50000);
        Log::ok($this->serverName . " shutdown by app: [OK]", TRUE);
    }


    public function run() {
        try {
//            echo __METHOD__ . PHP_EOL;
            $cmd = isset($_SERVER['argv'][1]) ? strtolower($_SERVER['argv'][1]) : 'help';
            switch ($cmd) {
                case 'stop':
                    $this->shutdown();
                    break;
                case 'start':
                    $this->initServer();
                    $this->start();
                    break;
                case 'reload':
                    $this->reload();
                    break;
                case 'restart':
                    $this->shutdown();
                    sleep(2);
                    $this->initServer();
                    $this->start();
                    break;
                case 'status':
                    $this->status();
                    break;
                default:
                    echo 'Usage:php app.php start | stop | reload | restart | status | help' . PHP_EOL;
                    break;
            }
        } catch (\Exception $e) {
            Log::err($e->getMessage().PHP_EOL.$e->getTraceAsString());
        }
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws SwoolfException
     */
    public function __call($name, $arguments) {
        if (isset(self::$facades[$name])) {
            return call_user_func_array(self::$facades[$name].'::i', $arguments);
        } else {
            throw new SwoolfException('unregister facade:'. $name);
        }
    }

    /**
     * get self instance.
     * @return null|App
     */
    public static function getInstance() {
        if (!self::$INSTANCE) {
            $argv = func_get_args();
            self::$INSTANCE = new self($argv[0]);
        }
        return self::$INSTANCE;
    }

}

class SwoolfException extends \Exception {
    public function __construct(string $message = "", int $code = 0, Throwable $previous = null)
    {
        parent::__construct('[Swoolf] '.$message, $code, $previous);
    }
}