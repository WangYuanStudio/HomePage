<?php
namespace App\Middles;
use App\Controllers\Session;
use App\Lib\Authorization;
use App\Lib\Response;

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
    	   Response::out(301);
           return false;
    	}
        
	}
    public function after($request)
    {
       
    }
}