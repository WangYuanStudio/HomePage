<?php
namespace App\Middles;

use App\Lib\Authorization;
use App\Controllers\Session;
use App\Models\Hws_Task;
use App\Lib\Response;

/*
 * Homework - WorkSubmit MiddleWare
 * Copyright @ WangYuanStudio
 *
 * Author: laijingwu
 * Last modified time: 2016-08-21 10:25
 */
class Hws_WorkSubmit implements MiddleWare
{
    public function before($request)
    {
    	// 获取角色ID
        $token = Session::get("user");
        $role_id = $token['rold'];

        if (isset($request['tid']) && $t = Hws_Task::where('id', '=', $request['tid'])->select()) {
            if (Authorization::isAuthorized($role_id, 'submit_'.$t[0]['department'].'_homeworks')) {
                return true;
            } else {
                Response::out(301);
                return false;
            }
        }
    }

    public function after($request)
    {
    }
}
?>