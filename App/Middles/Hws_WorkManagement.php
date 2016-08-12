<?php
namespace App\Middles;

use App\Lib\Authorization;

/*
 * Homework - WorkManagement MiddleWare
 * Copyright @ WangYuanStudio
 *
 * Author: laijingwu
 * Last modified time: 2016-08-12 16:21
 */
class Hws_WorkManagement implements MiddleWare
{
    public function before($request)
    {
    	// TODO: 获取角色ID
    	$role_id = 2;
    	$permission_request = [
    		'correct_homeworks',
    		'set_excellent_works',
    		'update_work',
    		'delete_work'
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