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
        return (new \App\Model\PDOModel('messages'))->add($msg);
    }

    public static function DBtestTask() {
        $db = new \App\Model\PDOModel();
        $app = \Swoolf\App::getInstance();
        while (TRUE) {
            $app->log::log($db->getAll('SHOW TABLES;'));
            sleep(1);
        }
    }

}