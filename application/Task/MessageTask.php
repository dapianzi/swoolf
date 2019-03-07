<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/5
 * Time: 11:09
 */
namespace APP\Task;
class MessageTask
{

    public static function reg() {

    }

    public static function saveMessageTask($msg) {
        return \Swoolf\App::getInstance()->db['pdo']->insert('messages', $msg);
    }

    public static function DBtestTask() {
        $app = \Swoolf\App::getInstance();
        while (TRUE) {
            $app->log::log($app->db['pdo']->getAll('SHOW TABLES;'));
            sleep(1);
        }
    }

}