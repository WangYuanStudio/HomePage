<?php
namespace App\Middles;
use App\Controllers\Session;
use App\Lib\Response;

class Check_login implements MiddleWare
{
    public function before($request)
    {
    	$token = Session::get("user");
    	if($token!=null)
    	{
            if(Session::get("user.ip")==$_SERVER["REMOTE_ADDR"])
            {
                return true;
            }
    		else
            {
                 Response::out(300);
            return false;
            }
    	}
    	else
    	{
            Response::out(300);
    		return false;
    	}
        
	}

    public function after($request)
    {
       
    }
}