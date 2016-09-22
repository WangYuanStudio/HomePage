<?php
namespace App\Middles;

use App\Controllers\Session;
use App\Lib\Response;
use App\Models\User;
use App\Controllers\Cache;


class Check_login implements MiddleWare
{
    public function before($request)
    {
        $_SESSION = json_decode(Cache::get($_SERVER["HTTP_AUTHENTICATION"]),true);

        $token = Session::get("user");
        if ($token != NULL) {
            if (Session::get("user.ip") == $_SERVER["REMOTE_ADDR"]) {
                return true;
            } else {
                Response::out(300);

                return false;
            }
        } else {
            if (isset($_COOKIE['token']))//判断存在token值
            {
                $d = User::where("token", "=", $_COOKIE['token'])->select();//寻找数据库与token值对应的数据
                if (sizeof($d) != 0)//找到
                {
                    if (strtotime(date("y-m-d H:i:s", time())) < strtotime($d[0]['overdue']))//判断是否过期token
                    {
                        $get_password = $d[0]['password'];
                        unset($d[0]['password']);
                        unset($d[0]['overdue']);
                        unset($d[0]['token']);
                        $d[0]['ip'] = $_SERVER["REMOTE_ADDR"];
                        Session::set("user", $d[0]);//保存session
                        if ($d[0]['status'] == 1) {
                            Response::out(300);

                            return false;
                        } else {
                            return true;
                        }

                    } else//过期后清空
                    {
                        $str = User::where('token', '=', $_COOKIE['token'])->update(['overdue' => NULL, 'token' => NULL]);
                        setcookie('token', '', time() - 360000, "/");
                        Response::out(300);

                        return false;
                    }


                } else {
                    Response::out(300);

                    return false;
                }
            } else {
                Response::out(300);

                return false;
            }
        }
    }

    public function after($request)
    {
        Cache::set($_SERVER["HTTP_AUTHENTICATION"], json_encode($_SESSION));
    }
}