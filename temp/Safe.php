<?php

/**
 * 基础安全过滤
 */
class SafePlugin extends Yaf_Plugin_Abstract
{

    public function routerStartup(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {
        $url_arr = array(
            'xss' => "\\=\\+\\/v(?:8|9|\\+|\\/)|\\%0acontent\\-(?:id|location|type|transfer\\-encoding)",
        );

        $args_arr = array(
            'xss' => "[\\'\\\"\\;\\*\\<\\>].*\\bon[a-zA-Z]{3,15}[\\s\\r\\n\\v\\f]*\\=|\\b(?:expression)\\(|\\<script[\\s\\\\\\/]|\\<\\!\\[cdata\\[|\\b(?:eval|alert|prompt|msgbox)\\s*\\(|url\\((?:\\#|data|javascript)",
            'sql' => "[^\\{\\s]{1}(\\s|\\b)+(?:select\\b|update\\b|insert(?:(\\/\\*.*?\\*\\/)|(\\s)|(\\+))+into\\b).+?(?:from\\b|set\\b)|[^\\{\\s]{1}(\\s|\\b)+(?:create|delete|drop|truncate|rename|desc)(?:(\\/\\*.*?\\*\\/)|(\\s)|(\\+))+(?:table\\b|from\\b|database\\b)|into(?:(\\/\\*.*?\\*\\/)|\\s|\\+)+(?:dump|out)file\\b|\\bsleep\\([\\s]*[\\d]+[\\s]*\\)|benchmark\\(([^\\,]*)\\,([^\\,]*)\\)|(?:declare|set|select)\\b.*@|union\\b.*(?:select|all)\\b|(?:select|update|insert|create|delete|drop|grant|truncate|rename|exec|desc|from|table|database|set|where)\\b.*(charset|ascii|bin|char|uncompress|concat|concat_ws|conv|export_set|hex|instr|left|load_file|locate|mid|sub|substring|oct|reverse|right|unhex)\\(|(?:master\\.\\.sysdatabases|msysaccessobjects|msysqueries|sysmodules|mysql\\.db|sys\\.database_name|information_schema\\.|sysobjects|sp_makewebtask|xp_cmdshell|sp_oamethod|sp_addextendedproc|sp_oacreate|xp_regread|sys\\.dbms_export_extension)",
            'other' => "\\.\\.[\\\\\\/].*\\%00([^0-9a-fA-F]|$)|%00[\\'\\\"\\.]"
        );

        $referer = empty($_SERVER['HTTP_REFERER']) ? array() : array($_SERVER['HTTP_REFERER']);
        $query_string = empty($_SERVER["QUERY_STRING"]) ? array() : array($_SERVER["QUERY_STRING"]);

        self::check_data($query_string, $url_arr);
        isset($_SERVER['SHELL']) ? self::swooleMode($referer,$args_arr) : self::normalMode($referer,$args_arr);       
    }

    /*
     *swoole模式下获取请求参数
     */
    private function swooleMode($referer,$args_arr)
    {
        $_GET = HttpServer::$get;
        $_POST = HttpServer::$post;
        $_COOKIE = HttpServer::$cookies;
        self::check_data(array($_GET, $_POST, $_COOKIE, $referer), $args_arr);
    }

    /*
     *正常模式下获取请求参数
     */
    private function normalMode($referer,$args_arr)
    {
        self::check_data(array($_GET, $_POST, $_COOKIE, $referer), $args_arr);
    }

    private function check_data($arr, $v)
    {
        if (!empty($arr)) {
            foreach ($arr as $key => $value) {
                is_array($key) ? self::check_data($key, $v) : self::check($key, $v);
                is_array($value) ? self::check_data($value, $v) : self::check($value, $v);
            }
        }
    }

    private function check($str, $v)
    {
        foreach ($v as $key => $value) {
            if (preg_match("/" . $value . "/is", $str) == 1 || preg_match("/" . $value . "/is", urlencode($str)) == 1) {
                // self::W_log();
                throw new Yaf_Exception('attack-请提交正确参数');
            }
            $v[$key] = htmlspecialchars($value);
        }
    }

    private function W_log()
    {
        
        $path = LogLibrary::logPath('attack');
        $data = [
            'IP' => $_SERVER['REMOTE_ADDR'],
            'URL' => $_SERVER['REQUEST_URI']
        ];
        LogLibrary::writeLog($path, '受到攻击', $data);
    }
}