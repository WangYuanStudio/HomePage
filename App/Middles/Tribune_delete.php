<?php
/**
 * Created by PhpStorm.
 * User: zeffee
 * Date: 2016/8/29
 * Time: 13:46
 */

namespace App\Middles;

use App\Controllers\Session;
use App\Lib\Authorization;
use App\Lib\Response;

class Tribune_delete implements MiddleWare
{
    public function before($request)
    {
        if (!Authorization::isAuthorized(Session::get("user.role"), "delete_post")) {
            Response::out(301);

            return false;
        }
    }

    public function after($request)
    {

    }
}