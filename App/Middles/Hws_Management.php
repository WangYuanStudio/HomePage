<?php
namespace App\Middles;

use App\Lib\Authorization;
use App\Controllers\Session;
use App\Models\Hws_Record;
use App\Models\Hws_Task;
use App\Lib\Response;
use Zereri\Lib\Register;

/*
 * Homework - Management MiddleWare
 * Copyright @ WangYuanStudio
 *
 * Author: laijingwu
 * Last modified time: 2016-08-24 18:31
 */
class Hws_Management implements MiddleWare
{
    public function before($request)
    {
        // 获取角色ID
        $token = Session::get("user");
        $role_id = $token['role'];

        // 添加任务 修改任务 判断部门合法性
        $department_array = ['backend', 'frontend', 'design', 'secret'];
        if ((isset($request['department']) && !in_array($request['department'], $department_array)) ||
            (isset($request['task_update']['department']) && !in_array($request['task_update']['department'], $department_array))
            ) {
            // 部门名称不合法
            Response::out(500);
            return false;
        }
        // 判断部门权限
        if ((isset($request['department']) && !Authorization::isAuthorized($role_id, 'manage_'.$request['department'].'_homeworks')) ||
            (isset($request['task_update']['department']) && !Authorization::isAuthorized($role_id, 'manage_'.$request['task_update']['department'].'_homeworks'))
            ) {
            // 不能越权处理，您可能不属于请求的部门
            Response::out(301);
            return false;
        }

        // 获取某任务的所有作业 获取单个作业 批改作业 设置优秀作业 修改作业信息 删除作业 修改任务 删除任务 手动截止任务  判断权限
        if ((isset($request['rid']) &&
            $t = Hws_Record::belongsToMany("getTask")->where('Hws_Record.id', '=', $request['rid'])->select()) ||
            isset($request['tid']) && $t = Hws_Task::where('id', '=', $request['tid'])->select()
        ) {
            if (!Authorization::isAuthorized($role_id, 'manage_'.$t[0]['department'].'_homeworks')) {
                // 不能越权处理，您可能不属于请求的部门
                Response::out(301);
                return false;
            }
        }

        return true;
    }

    public function after($request)
    {
    }
}
?>