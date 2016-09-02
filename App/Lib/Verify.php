<?php
/**
 * Created by PhpStorm.
 * User: zeffee
 * Date: 2016/9/2
 * Time: 21:59
 */

/**
 * Usage:
 *      if(Verify::auth()){
 *          //code
 *      }
 */

namespace App\Lib;

use App\Controllers\Session;

class Verify
{
    public static function auth()
    {
        if (1 !== Session::get("verify_auth")) {
            Response::out(302);

            return false;
        }

        Session::remove("verify_auth");
    }
}