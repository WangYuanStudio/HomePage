<?php
namespace App\Middles;

use App\Lib\Authorization;
use App\Controllers\Session;
use App\Models\Hws_Record;
use App\Models\Hws_Task;
use App\Lib\Response;

/*
 * Homework - Management MiddleWare
 * Copyright @ WangYuanStudio
 *
 * Author: laijingwu
 * Last modified time: 2016-08-21 10:24
 */
class Hws_Management implements MiddleWare
{
    public function before($request)
    {
        // 获取角色ID
        $token = Session::get("user");
        $role_id = $token['rold'];

        // 批改作业 设置优秀作业 修改作业信息 删除作业  修改任务 删除任务 手动截止任务  判断权限
        if ((isset($request['rid']) &&
            $t = Hws_Record::belongsToMany("getTask")->where('Hws_Record.id', '=', $request['rid'])->select()) ||
            isset($request['tid']) && $t = Hws_Task::where('id', '=', $request['tid'])
        ) {
            if (!Authorization::isAuthorized($role_id, 'manage_'.$t[0]['department'].'_homeworks')) {
                Response::out(301);
                return false;
            }
        }

        // 添加任务 修改任务 判断权限
        $department_array = ['backend', 'frontend', 'design', 'secret'];
        if ((isset($request['department']) && !in_array($request['department'], $department_array)) ||
            (isset($request['task_update']['department']) && !in_array($request['task_update']['department'], $department_array))
            ) {
            Response::out(500);
            return false;
        }
        return true;
    }

    public function after($request)
    {
    }
}
?>