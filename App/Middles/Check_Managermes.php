<?php
namespace App\Middles;
use App\Controllers\Session;
use App\Lib\Authorization;
use App\Lib\Response;

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
    	   Response::out(301);
           return false;
    	}
        
	}
    public function after($request)
    {
       
    }
}