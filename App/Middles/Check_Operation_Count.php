<?php
/**
 * Created by PhpStorm.
 * User: zeffee
 * Date: 2016/8/21
 * Time: 16:32
 */

namespace App\Middles;

use App\Controllers\Session;
use App\Controllers\Cache;
use App\Lib\Response;

class Check_Operation_Count implements MiddleWare
{
    public function before($request)
    {
        $user = Session::get("user.id") ?: $_SERVER["REMOTE_ADDR"];

        if (Cache::get($user) > 3) {
            Cache::EXPIRE($user, 300);
            Response::out(303);

            return false;
        }
        Cache::increment($user);
        Cache::EXPIRE($user, 10);

        //Cache::remove($user);
    }

    public function after($request)
    {

    }
}