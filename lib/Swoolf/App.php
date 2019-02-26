<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/14
 * Time: 17:53
 */

namespace Swoolf;

use Throwable;

class App
{

    protected static $INSTANCE = [];

    public $conf = NULL;
    /*
     * Debug
     */
    public $debug = TRUE;
    public $logFile = './swoolf.log';

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

    public function __construct($ini_file)
    {

        // parse ini config
        $this->conf = $this->parseIni($ini_file);
        // global config

        // log config
        if (isset($this->conf['debug'])) {
            $this->debugConf($this->conf['debug']);
        }
        // server config
        $this->serverSettings = $this->defaultServerSettings();
        if (isset($this->conf['server'])) {
            $this->serverConf($this->conf['server']);
        }
        // register facades
        $this->facade::reg('loader', __NAMESPACE__.'\Loader');
        $this->facade::reg('log', __NAMESPACE__.'\Log');
        $this->facade::reg('utils', __NAMESPACE__.'\Utils');
        $this->facade::reg('event', __NAMESPACE__.'\Event');
//        $this->facade::reg('table', __NAMESPACE__.'\Table');

        // init table
        $this->table = new \Swoole\Table(1024);
        $this->table->column('id', \Swoole\Table::TYPE_INT, 4);       //1,2,4,8
        $this->table->column('name', \Swoole\Table::TYPE_STRING, 64);
        $this->table->column('icon', \Swoole\Table::TYPE_STRING, 255);
        $this->table->create();
        self::$INSTANCE[get_called_class()] = $this;
    }

    public function parseIni($file) {
        $conf = Utils::parseInFile($file);
        if (defined('APP_ENV') && APP_ENV == 'product') {
            return isset($conf['product']) ? $conf['product'] : $conf['common'];
        }
        return isset($conf['develop']) ? $conf['develop'] : $conf['common'];
    }

    public function debugConf($conf) {
        if (isset($conf['debug'])) {
            Log::setDebug($conf['debug']);
        }
        if (isset($conf['log_file'])) {
            Log::setLogFile($conf['log_file']);
        }
    }

    public function defaultServerSettings() {
        return [
            'worker_num' => 4,
            'user' => 'root',
            'group' => 'root',
            'daemonize' => 1,
            'log_file' => '/tmp/swoole.http.log',
            'pid_file' => '/tmp/server.http.pid',
            'log_level' => SWOOLE_LOG_DEBUG,
            'max_request' => 5,
        ];
    }

    public function serverConf($conf) {

        if (isset($conf['type'])) {
            $this->serverType = $conf['type'];
        }
        if (isset($conf['name'])) {
            $this->serverName = $conf['name'];
        }
        if (isset($conf['host'])) {
            $this->serverHost = $conf['host'];
        }
        if (isset($conf['port'])) {
            $this->serverPort = $conf['port'];
        }
        if (isset($conf['mode'])) {
            $this->serverMode = $conf['mode'];
        }
        if (isset($conf['settings'])) {
            $this->serverSettings = array_merge($this->serverSettings, $conf['settings']);
        }
    }

    protected function initServer() {
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

    protected function bind() {
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

        }
    }

    public function on($event, $func) {
        if ($this->server) {
            $this->server->on($event, $func);
        }
    }

    public function onStart($server) {
        Log::info($this->serverName . ' master process start [OK].');
        Log::info(sprintf('master pid [%d], manager pid [%d]', $server->master_pid, $server->manager_pid));
        swoole_set_process_name($this->serverName.'-master');
    }

    public function onManagerStart($server) {
        Log::info($this->serverName . ' manager process start [OK].');
        swoole_set_process_name($this->serverName.'-manager');
    }

    public function onShutdown($server) {
        Log::warm($this->serverName . ' shutdown. BYE~');
    }

    public function onWorkerStart($server, $worker_id) {
        if ($server->taskworker) {
            Log::info(sprintf('task [%d] begin..', $worker_id));
        } else {
            Log::info(sprintf('worker [%d] start..', $worker_id));
            swoole_set_process_name($this->serverName. '-worker-'.$worker_id);
        }
    }

    public function onWorkerStop($server, $worker_id) {
        if ($server->taskworker) {
            Log::ok(sprintf('task [%d] finished.', $worker_id));
        } else {
            Log::warm(sprintf('worker [%d] stop.', $worker_id));
        }
    }

    public function onWorkerExit($server, $worker_id) {
        Log::warm('Worker ['. $worker_id .'] is exiting..');
    }

    /************************ http server ************************/
    public function onHttpRequest($request, $response) {
        Log::log('Server on request fd['.$request->fd.']..');
        $response->end('swoole default http request.');
    }

    /************************ web socket ************************/
    public function onWSOpen($server, $request) {
        Log::info('Server handshake success with fd['.$request->fd.'].');
    }

    public function onWSClose($server, $fd) {
        Log::warm('Client fd['.$fd.'] closed.');
    }

    public function onWSMessage($server, $frame) {
//        Log::log(sprintf('Receive from client[%d]: %s,opcode: %s, fin: %s', $frame->fd, $frame->data, $frame->opcode, $frame->finish));
//        $server->push($frame->fd, 'This is server: '.$frame->data);
        foreach ($server->connections as $fd) {
            if ($fd == $frame->fd) {
                continue;
            }
            $info = $server->connection_info($fd);
            if ($info['websocket_status'] == WEBSOCKET_STATUS_ACTIVE) {
                $server->push($fd, '['.$frame->fd.']:'.$frame->data);
            }
        }
    }

    /************************ tcp socket ************************/





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
     * magic method for facades.
     * @param $name
     * @return mixed
     * @throws SwoolfException
     */
    public function __get($name) {
        if (isset(Facade::$facades[$name])) {
            return Facade::$facades[$name]::i();
        } else {
            throw new SwoolfException('unregister facade:'. $name);
        }
    }

    /**
     * get self instance.
     * @return null|App
     */
    public static function getInstance() {
        $cls = get_called_class();
        if (!isset(self::$INSTANCE[$cls])) {
            $argv = func_get_args();
            self::$INSTANCE[$cls] = new $cls($argv[0]);
        }
        return self::$INSTANCE[$cls];
    }

}

class SwoolfException extends \Exception {
    public function __construct(string $message = "", int $code = 0, Throwable $previous = null)
    {
        parent::__construct('[Swoolf] '.$message, $code, $previous);
    }
}