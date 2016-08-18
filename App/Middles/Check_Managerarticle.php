<?php
namespace App\Middles;
use App\Controllers\Session;
use App\Lib\Authorization;


class Check_Managerarticle implements MiddleWare
{
    public function before($request)
    {
    	$role_id = Session::get("user.role");
    	if(Authorization::isAuthorized($role_id,"动态管理"))
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