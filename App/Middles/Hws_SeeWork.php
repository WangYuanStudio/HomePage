<?php
namespace App\Middles;

use App\Lib\Authorization;
use App\Controllers\Session;
use App\Lib\Response;
use Zereri\Lib\Register;

/*
 * Homework - See Homeworks MiddleWare
 * Copyright @ WangYuanStudio
 *
 * Author: laijingwu
 * Last modified time: 2016-08-24 00:43
 */
class Hws_SeeWork implements MiddleWare
{
    public function before($request)
    {
    	// 获取角色ID
        $token = Session::get("user");
        $role_id = $token['role'];

        // 除外：获取全部任务
        if (Register::get("method") == 'getAllTasks' || 
            Authorization::isAuthorized($role_id, 'see_homework')) {
            return true;
        } else {
            Response::out(506);
            return false;
        }
    }

    public function after($request)
    {
    }
}
?>