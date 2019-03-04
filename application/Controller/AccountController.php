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
        $token = $this->request->msg_data->getToken();
        $db = new UserModel();
        $user = $db->getRow('SELECT * FROM users WHERE token=?', [$token]);

        if ($user) {
            $chats = $db->getAll('SELECT * FROM chats WHERE users = ?', [$user['id']]);
            $chatMsg = [];
            foreach ($chats as $c) {
                $charMsg[] = new \Proto\Chat([
                    'err' => 0,
                    'id' => intval($c['id']),
                    'icon' => $c['icon'],
                    'last_time' => intval($c['last_time']),
                    'last_msg' => $c['last_msg'],
                ]);
            }
            $this->response(1004, [
                'id' => $user['id'],
                'username' => $user['username'],
                'avatar' => $user['avatar'],
//                'friends' => $this->fd,
                'chats' => $chatMsg
            ]);
            // init login
//            $this->app->event::emit('login', $user);
        } else {
            $this->response(1004, ['err' => 1]);
        }
    }

    public function RegisterAction() {
        $msg = $this->request->msg_data;
        $username = $msg->getUsername();
        $password = $msg->getPassword();
        $avatar = $msg->getAvatar();
        $db = new \App\Model\UserModel();

        $app = \Swoolf\App::getInstance();
        $user = $db->getRow('SELECT * FROM users WHERE username=?', [$username]);
        if ($user) {
            // login
            if ($app->utils::encryptStr($password) == $user['password']) {
                $this->response(1002, [
                    'err' => 0,
                    'token' => $user['token']
                ]);
            } else {
                $this->response(1002, ['err' => 1]);
            }
        } else {
            // save to db
            $token = $app->utils::randomStr(32);
            $id = $db->add([
                'username' => $username,
                'password' => $app->utils::encryptStr($password),
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
        $this->app->event::emit('logout', $this->fd);
        $this->app->server->close($this->fd);// close socket
    }


    public function GetHistoryMessage() {

    }

}