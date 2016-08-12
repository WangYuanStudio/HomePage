<?php
namespace App\Middles;

use App\Lib\Authorization;

/*
 * Homework - WorkSubmit MiddleWare
 * Copyright @ WangYuanStudio
 *
 * Author: laijingwu
 * Last modified time: 2016-08-12 16:20
 */
class Hws_WorkSubmit implements MiddleWare
{
    public function before($request)
    {
    	// TODO: 获取角色ID
    	$role_id = 2;
    	if (Authorization::isAuthorized($role_id, 'submit_homeworks')) {
    		return true;
    	} else {
	    	response(['status' => 1, 'msg' => '无权限']);
	    	return false;
	    }
    }

    public function after($request)
    {
    }
}

?>