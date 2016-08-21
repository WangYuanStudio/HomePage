<?php
/**
 * Created by PhpStorm.
 * User: zeffee
 * Date: 2016/8/21
 * Time: 16:32
 */

namespace App\Middles;

class Check_Operation_Count implements MiddleWare
{
    public function before($request)
    {
        $user = Session::get("user.id") ?: $_SERVER["REMOTE_ADDR"];

        if (1 === Cache::get($user . "_verify")) {
            Response::out(303);

            return false;
        }

        Cache::increment($user);
        Cache::EXPIRE($user, 10);
        if (Cache::get($user) > 3) {
            Cache::set($user . "_verify", 1);
            Response::out(303);

            return false;
        }

        //Cache::remove($user);
    }

    public function after($request)
    {

    }
}