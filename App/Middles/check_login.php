<?php
namespace App\Middles;
use App\Controllers\session;

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
    		$data = ["status" => "0"];
            response($data, "json");
    		return false;
    	}
        
	}

    public function after($request)
    {
       
    }
}