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
}