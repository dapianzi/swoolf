<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/15
 * Time: 10:08
 */

namespace Swoolf;


class Utils implements Interfaces\FacadeInterface
{

    public static function i() {
        return __CLASS__;
    }

    public static function now($time = 0, $format = 'Y-m-d H:i:s') {
        $time = empty($time) ? time() : $time;
        return date($format, $time);
    }

    /**
     * 字符串哈希算法
     * @param $str
     * @param $salt
     * @return string
     */
    public static function encryptStr($str, $salt='') {
        return sha1($str . $salt);
    }

    public static function http_post($url,$parameters = NULL, $headers = array()){
        return self::http($url,'post' ,$parameters , $headers );
    }

    public static function http_get($url, $parameters = NULL, $headers = array()){
        return self::http($url,'get' ,$parameters , $headers );
    }

    public static function http($url, $method, $parameters = NULL, $headers = array()) {
        if(empty($url)){return NULL;}
        $ch = curl_init();
        /* Curl settings */
        curl_setopt($ch, CURLOPT_HTTP_VERSION           , CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER         , TRUE);
        curl_setopt($ch, CURLOPT_TIMEOUT                , 10);
        curl_setopt($ch, CURLOPT_HEADER                 , FALSE);
//    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER         , FALSE); // 跳过证书检查
//    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST         , 2);  // 从证书中检查SSL加密算法是否存在
        if(substr($url, 0,5) == 'https') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        switch (strtolower($method)) {
            case 'post':
                curl_setopt($ch, CURLOPT_POST, TRUE);
                if (!empty($parameters)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
                }
                break;
            case 'get':
                if (!empty($parameters)) {
                    $url .= strpos($url, '?') === false ? '?' : '&';
                    $url .= http_build_query($parameters);
                }
                break;
            default:
                # code...
                break;
        }
        curl_setopt($ch, CURLOPT_URL                    , $url );
        curl_setopt($ch, CURLOPT_HTTPHEADER             , $headers);
        curl_setopt($ch, CURLINFO_HEADER_OUT            , TRUE );
        $response = curl_exec($ch);
        curl_close ($ch);
        return $response;
    }

    public static function randomStr($n) {
        if (!is_int($n)) {
            throw new \Exception('argument must be int');
        }
        $alpha = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $str = '';
        for ($i=0; $i<$n; $i++) {
            $str .= $alpha[rand(0, 35)];
        }
        return $str;
    }

    public static function arrayValue($var, $key, $default='') {
        return isset($var[$key]) ? $var[$key] : $default;
    }

    public static function setArrayAssoc($array, $key) {
        $ret = [];
        foreach ($array as $k=>$v) {
            if (isset($v[$key])) {
                $ret[$v[$key]] = $v;
            }
        }
        return $ret;
    }

    public static function setObjectAssoc($array, $key) {
        $ret = [];
        foreach ($array as $r) {
            if (isset($r->$key)) {
                $ret[$r->$key] = $r;
            }
        }
        return $ret;
    }

    public static function toUTF8($str) {
        return iconv('gbk', 'utf-8', $str);
    }

    public static function toGBK($str) {
        return iconv('utf-8', 'gbk', $str);
    }

    public static function dateRange($from, $n, $step='d') {
        $range = [];
        switch (strtolower($step)) {
            case 'r':
                $format = 'H:i:s';
                $interval = '10 minutes';
                break;
            case 'h':
                $format = 'Y-m-d H';
                $interval = '1 hour';
                break;
            case 'w':
                $format = 'Y-m-d';
                $interval = '1 week';
                $from = date('Y-m-d', strtotime($from.' LAST SUNDAY'));
                break;
            case 'm':
                $format = 'Y-m-01';
                $interval = '1 month';
                break;
            case 'd':
            default:
                $format = 'Y-m-d';
                $interval = '1 day';
                break;
        }
        $from = self::now(strtotime($from), $format);
        $i = 0;
        while ($i < $n) {
            $range[] = $from;
            $from = self::now(strtotime($from.' + '.$interval), $format);
            $i ++;
        }
        return $range;
    }

    public static function percent($val, $base, $decimal=0) {
        return $base==0 ? '-' : round($val*100/$base, $decimal);
    }

    public static function division($val, $base, $decimal=0) {
        return $base==0 ? '-' : round($val/$base, $decimal);
    }

    public static function growth($base, $val) {
        if ($base > 0) {
            return self::percent($val - $base, $base);
        } else {
            return $base;
        }
    }

    public static function arr2xml($data, $root = true){
        $str = "";
        if ($root) {
            $str .= "<xml>";
        }
        foreach($data as $key => $val){
            if(is_array($val)){
                $child = self::arr2xml($val, false);
                $str .= "<{$key}>{$child}</{$key}>";
            }else{
                $str.= "<{$key}><![CDATA[{$val}]]></{$key}>";
            }
        }
        if ($root) {
            $str .= "</xml>";
        }
        return $str;
    }

    /**
     * @param $a
     * @param $b
     * @return array
     */
    public static function arrayAdd($a, $b) {
        $ka = is_array($a) ? array_keys($a) : [];
        $kb = is_array($b) ? array_keys($b) : [];
        $keys = array_unique(array_merge($ka, $kb));
        $ret = [];
        foreach($keys as $k) {
            if ((isset($a[$k])&&is_array($a[$k])) || (isset($b[$k])&&is_array($b[$k]))) {
                $ta = isset($a[$k]) ? $a[$k] : [];
                $tb = isset($b[$k]) ? $b[$k] : [];
                $ret[$k] = self::arrayAdd($ta, $tb);
            } else {
                if (!isset($a[$k])) {
                    $ret[$k] = $b[$k];
                } else if (!isset($b[$k])) {
                    $ret[$k] = $a[$k];
                } else {
                    $ret[$k] = $a[$k] + $b[$k];
                }
            }
        }
        return $ret;
    }

    public static function arraySum($arr, $col='') {
        if (!is_array($arr)) {
            return 0;
        }
        if (empty($col)) {
            return array_sum($arr);
        }
        $sum = 0;
        foreach ($arr as $v) {
            $sum += $v[$col];
        }
        return $sum;
    }

    public static function arraySort(&$arr, $key, $asc=SORT_DESC) {
        $tmp = [];
        foreach ($arr as $v) {
            $tmp[] = isset($v[$key]) ? $v[$key] : 0;
        }
        array_multisort($tmp, $asc, $arr);
    }

    public static function dateAdd($d, $add) {
        return date('Y-m-d', strtotime($d.' '.$add.' days'));
    }


    /**
     * 改变进程的用户ID
     * @param $user
     */
    static function changeUser($user) {
        if (!function_exists('posix_getpwnam')) {
            trigger_error(__METHOD__ . ": require posix extension.");
            return;
        }
        $user = posix_getpwnam($user);
        if ($user) {
            posix_setuid($user['uid']);
            posix_setgid($user['gid']);
        }
    }

    static function setProcessName($name) {
        if (function_exists('cli_set_process_title')) {
            cli_set_process_title($name);
        } else if (function_exists('swoole_set_process_name')) {
            swoole_set_process_name($name);
        } else {
            trigger_error(__METHOD__ . " failed. require cli_set_process_title or swoole_set_process_name.");
        }
    }

    /**
     * 全角转半角
     *
     * @param	string  $str    原字符串
     * @return  string  $str    转换后的字符串
     */
    static function sbc2abc($str) {
        $f = array ('　', '０', '１', '２', '３', '４', '５', '６', '７', '８', '９', 'ａ', 'ｂ', 'ｃ', 'ｄ', 'ｅ', 'ｆ', 'ｇ', 'ｈ', 'ｉ', 'ｊ', 'ｋ', 'ｌ', 'ｍ', 'ｎ', 'ｏ', 'ｐ', 'ｑ', 'ｒ', 'ｓ', 'ｔ', 'ｕ', 'ｖ', 'ｗ', 'ｘ', 'ｙ', 'ｚ', 'Ａ', 'Ｂ', 'Ｃ', 'Ｄ', 'Ｅ', 'Ｆ', 'Ｇ', 'Ｈ', 'Ｉ', 'Ｊ', 'Ｋ', 'Ｌ', 'Ｍ', 'Ｎ', 'Ｏ', 'Ｐ', 'Ｑ', 'Ｒ', 'Ｓ', 'Ｔ', 'Ｕ', 'Ｖ', 'Ｗ', 'Ｘ', 'Ｙ', 'Ｚ', '．', '－', '＿', '＠' );
        $t = array (' ', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '.', '-', '_', '@' );
        $str = str_replace ( $f, $t, $str );
        return $str;
    }

    /**
     * 微信表情处理
     * @param $str
     * @param string $method
     * @return null|string|string[]
     */
    static function uicode_z($str,$method='en') {
        if($method=='en'){
            return preg_replace_callback('/[\xf0-\xf7].{3}/',function($r){return '@E'.base64_encode($r[0]);},$str);
        }else{
            return preg_replace_callback('/@E(.{6}==)/', function($r){return base64_decode($r[1]);},$str);
        }
    }

    /**
     * Parses INI file adding extends functionality via ":base" postfix on namespace.
     *
     * @param string $filename
     * @return array
     */
    static function parseInFile($filename) {
        $p_ini = parse_ini_file($filename, true);
        $config = array();
        foreach($p_ini as $namespace => $properties){
            if (strpos($namespace, ':') > 0) {
                list($name, $extends) = explode(':', $namespace);
            } else {
                $name = $namespace;
                $extends = null;
            }
            $name = trim($name);
            $extends = trim($extends);
            // create namespace if necessary
            if(!isset($config[$name])) $config[$name] = array();
            // inherit base namespace
            if(isset($p_ini[$extends])){
                foreach($p_ini[$extends] as $prop => $val)
                    $config[$name][$prop] = $val;
            }
            // overwrite / set current namespace values
            foreach($properties as $prop => $val){
                $config[$name][$prop] = $val;
            }
        }
        $ret = [];
        foreach ($config as $name=>$props) {
            $group = [];
            foreach ($props as $k=>$v) {
                $group = self::arrayExtend($group, self::parseKeyValue($k, $v));
            }
            $ret[$name] = $group;
        }
        return $ret;
    }

    static function parseKeyValue($key, $value) {
        $ret = null;
        if (strpos($key, '.') > 0) {
            list($k1, $k2) = explode('.', $key, 2);
            $ret[$k1] = self::parseKeyValue($k2, $value);
        } else {
            $ret[$key] = $value;
        }
        return $ret;
    }

    static function arrayExtend($arr1, $arr2) {
        $ret = [];
        foreach ($arr1 as $k=>$v) {
            if(isset($arr2[$k])) {
                $ret[$k] = self::arrayExtend($v, $arr2[$k]);
                unset($arr2[$k]);
            } else {
                $ret[$k] = $v;
            }
        }
        return array_merge($ret, $arr2);
    }
}