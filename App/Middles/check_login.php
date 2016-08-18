<?php
namespace App\Middles;
use App\Controllers\Session;

class check_login implements MiddleWare
{
    public function before($request)
    {
    	$token = Session::get("user");
    	if($token!=null)
    	{
    		return true;
    	}
    	else
    	{
    		$status= ['status' => '300','errmsg'=>'Invalid login status.','data'=>''];
            response($status, "json");
    		return false;
    	}
        
	}

    public function after($request)
    {
       
    }
}