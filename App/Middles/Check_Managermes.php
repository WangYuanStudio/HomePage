<?php
namespace App\Middles;
use App\Controllers\Session;
use App\Lib\Authorization;


class Check_Managermes implements MiddleWare
{
    public function before($request)
    {
    	$role_id = Session::get("user.role");
    	if(Authorization::isAuthorized($role_id,"留言管理"))
    	{
    		return true;
    	}
    	else
    	{
    	   $status=['status' => "301",'errmsg' =>  config("common_status")['301'],'data'=>''];
           response($status,"json");
           return false;
    	}
        
	}
    public function after($request)
    {
       
    }
}