<?php
/*
 * Created by PhpStorm.
 * User: zeffee
 * Date: 2016/8/23
 * Time: 14:36
 */

namespace App\Controllers;

use App\Lib\Response;
use App\Models\Inform;

class Common
{
    /**公共类-获取验证码图片
     *
     */
    public function verifyImg()
    {
        $v = new \App\Lib\Vcode('img', 2, 18, 150, 250, false, true, 0, 0, __ROOT__ . "/public/" . mt_rand(1, 19) . ".jpg", __ROOT__ . '/msyhbd.ttc', [255, 250, 250]);
        Session::set("vcode", $v->getData());
        $v->show();
    }


    /**公共类-获取验证码的提示
     *
     */
    public function verifyType()
    {
        response(Session::get("vcode.type"));
    }


    /**公共类-验证验证码
     *
     * @param array $text 验证码文本
     *
     * @return status.状态码
     */
    public function verify($text)
    {
        if ($v = Session::get("vcode.text")) {
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

            Cache::delete(Session::get("user.id") ?: $_SERVER["REMOTE_ADDR"]);
            Response::out(200);
        } else {
            Response::out(302);
        }
    }


    /* 添加用户通知
     *
     * @param int    $uid      用户id
     * @param string $classify 分类(论坛，报名，作业，官网)
     * @param string $content  通知的内容(简述)
     * @param string $url      跳转地址
     *
     * @return bool
     */
    public static function setInform($uid, $classify, $content, $url)
    {
        Inform::insert([
            "uid"      => $uid,
            "classify" => $classify,
            "content"  => $content,
            "url"      => $url,
            "time"     => date("Y-m-d H:i:s")
        ]);

        return true;
    }


    /**获取我的通知列表
     *
     */
    public function getInform()
    {
        if ($data = Inform::whereAndWhere(["uid", "=", Session::get("user.id")], ["is_read", "=", 0])
            ->orderBy("time desc")
            ->select()
        ) {
            Inform::whereAndWhere(["uid", "=", Session::get("user.id")], ["is_read", "=", 0])
                ->update([
                    "is_read" => 1
                ]);
        }

        Response::out(200, $data);
    }


    /**获取我的通知数目
     *
     */
    public function getInformNum()
    {
        $num = Inform::whereAndWhere(["uid", "=", Session::get("user.id")], ["is_read", "=", 0])
            ->count("count")
            ->select("")[0]["count"];

        Response::out(200, ["num" => $num]);
    }
}