<?php
namespace App\Middles;

use App\Lib\Authorization;

/*
 * Homework - TaskManagement MiddleWare
 * Copyright @ WangYuanStudio
 *
 * Author: laijingwu
 * Last modified time: 2016-08-12 16:20
 */
class Hws_TaskManagement implements MiddleWare
{
    public function before($request)
    {
    	// TODO: 获取角色ID
    	$role_id = 2;
    	$permission_request = [
    		'add_task',
    		'update_task',
    		'delete_task',
    		'set_task_off'
    	];
    	$allowed = true;
    	foreach ($permission_request as $value) {
    		if (!Authorization::isAuthorized($role_id, $value)) {
    			$allowed = false;
    			response(['status' => 1, 'msg' => '无权限']);
	    		return $allowed;
	    	}
    	}
    	return $allowed;
    }

    public function after($request)
    {
    }
}

?>