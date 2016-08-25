<?php
/**
 * Created by PhpStorm.
 * User: zeffee
 * Date: 2016/8/23
 * Time: 14:36
 */

namespace App\Controllers;

use App\Lib\Response;

class Common
{
    /**
     * 获取验证码图片
     */
    public function verifyImg()
    {
        $v = new \App\Lib\Vcode('img', 2, 18, 150, 250, false, true, 0, 0, __ROOT__ . "/public/" . mt_rand(1, 19) . ".jpg", __ROOT__ . '/msyhbd.ttc', [255, 250, 250]);
        Session::set("vcode", $v->getData());
        $v->show();
    }


    /**
     * 获取验证码的提示
     */
    public function verifyType()
    {
        response(Session::get("vcode")["type"]);
    }


    /**验证验证码
     *
     * @param array $text 验证码文本
     *
     * @return status.状态码
     */
    public function verify($text)
    {
        $v = Session::get("vcode")["text"];
        Session::remove("vcode");
        foreach ($text as $key => $value) {
            if ($value["x"] > $v[ $key ]["max_x"]
                || $value["x"] < $v[ $key ]["min_x"]
                || $value["y"] > $v[ $key ]["max_y"]
                || $value["y"] < $v[ $key ]["min_y"]
            ) {
                Response::out(302);
                die();
            }
        }

//        Cache::remove(Session::get("user.id") ?: $_SERVER["REMOTE_ADDR"]);
        Response::out(200);
    }
}