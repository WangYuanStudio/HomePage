<?php
namespace App\Middles;
use App\Controllers\Session;
use App\Lib\Response;

class Check_adminstrator implements MiddleWare
{
    public function before($request)
    {
        if(Session::get("user.role")!=1)
        {
             Response::out(301);
             return false;
        }
        
    }
    public function after($request)
    {
       
    }
}