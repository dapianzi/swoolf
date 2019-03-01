<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/1
 * Time: 19:40
 */
namespace App\Controller;

class AccountController extends Controller
{

    public function LoginAction() {
        $username = $this->request->msg_data->getUsername();
        $password = $this->app->utils::encryptStr($this->request->msg_data->getPassword());
        if ($username == 'dapianzi' && '40bd001563085fc35165329ea1ff5c5ecbdbbeef' == $password) {
            $this->response(1002, [
                'id' => $this->fd,
                'token' => $this->app->utils::randomStr(32)
            ]);
            // init login
            $this->app->event::emit('login', $this->fd);
        } else {
            $this->response(1002, ['op' => 1]);
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