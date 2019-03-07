<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/1
 * Time: 19:40
 */
namespace App\Controller;

use App\Model\UserModel;

class AccountController extends Controller
{

    public function LoginAction() {
        $token = $this->getData()->getToken();
        $db = $this->getDB('pdo');
        $user = $db->getRow('SELECT * FROM users WHERE token=?', [$token]);

        if ($user) {
//            $chats = $db->getAll('SELECT * FROM chats WHERE users = ?', [$user['id']]);
            $chatMsg = [];
//            foreach ($chats as $c) {
//                $charMsg[] = new \Proto\Chat([
//                    'err' => 0,
//                    'id' => intval($c['id']),
//                    'icon' => $c['icon'],
//                    'last_time' => intval($c['last_time']),
//                    'last_msg' => $c['last_msg'],
//                ]);
//            }
            $friends = [];
            $redis = $this->getRedis();
            $users = $redis->sMembers('user');
            foreach ($users as $u) {
                $role = $redis->getArr('user:'.$u);
                $friends[] = new \Proto\Role([
                    'id' => intval($role['id']),
                    'name' => $role['name'],
                    'avatar' => $role['avatar']
                ]);
            }
            $this->response(1004, [
                'id' => $user['id'],
                'username' => $user['username'],
                'avatar' => $user['avatar'],
                'friends' => $friends,
//                'chats' => $chatMsg
            ]);
            // init login
            $role = [
                'id' => $user['id'],
                'name' => $user['username'],
                'avatar' => $user['avatar'],
            ];
//            $this->app->event::emit('login', $user);
            $redis->sAdd('user', intval($user['id']));
            $redis->setArr('user:'.$user['id'], $role);
            $response = $this->encode(1009, [
                'role' => new \Proto\Role($role)
            ]);
            $this->getServer()->task(['task_id' => 1000, 'fd' => $this->fd, 'response' => $response]);
        } else {
            $this->response(1004, ['err' => 1]);
        }
    }

    public function RegisterAction() {
        $msg = $this->request->msg_data;
        $username = $msg->getUsername();
        $password = $msg->getPassword();
        $avatar = $msg->getAvatar();

        $db = $this->getDB('pdo');
        $user = $db->getRow('SELECT * FROM users WHERE username=?', [$username]);
        if ($user) {
            // login
            if ($this->app->utils()::encryptStr($password) == $user['password']) {
                $this->response(1002, [
                    'err' => 0,
                    'token' => $user['token']
                ]);
            } else {
                $this->response(1002, ['err' => 1]);
            }
        } else {
            // save to db
            $token = $this->app->utils()::randomStr(32);
            $id = $db->add([
                'username' => $username,
                'password' => $this->app->utils()::encryptStr($password),
                'avatar' => $avatar,
                'token' => $token
            ]);
            $this->response(1002, [
                'err' => 0,
                'token' => $token
            ]);
        }
    }

    public function GetFriendListAction() {
        $list = [];
        foreach ($this->app->table as $r) {
            $list[] = new \Proto\Role([
                'id' => $r['id'],
                'name' => $r['name'],
                'icon' => $r['icon'],
            ]);
        }
        $this->response(1004, ['list' => $list]);
    }

    public function LogoutAction() {
        $this->response(1006);
        $this->app->event()::emit('logout', $this->fd);
        $this->getServer()->close($this->fd);// close socket
    }


    public function GetHistoryMessage() {

    }

}