<?php
namespace App\Middles;

use App\Controllers\Session;
use App\Lib\Response;
use App\Models\User;
use App\Controllers\Cache;


class Check_login implements MiddleWare
{
    public function before($request)
    {
        $_SESSION = json_decode(Cache::get($_SERVER["HTTP_AUTHENTICATION"]),true);

        $token = Session::get("user");
        if ($token != NULL) {
            if (Session::get("user.ip") == $_SERVER["REMOTE_ADDR"]) {
                return true;
            } else {
                Response::out(300);

                return false;
            }
        } 
    }

    public function after($request)
    {
        Cache::set($_SERVER["HTTP_AUTHENTICATION"], json_encode($_SESSION));
    }
}