<?php
/**
 * Created by PhpStorm.
 * User: zeffee
 * Date: 2016/9/15
 * Time: 18:13
 */

/**
 * Usage:
 *      if(Message::send(13580104633, 123456)){
 *          //code
 *      }
 */
namespace App\Lib;


class Message
{
    /**发送手机验证码
     *
     * @param int $phone 长号
     * @param int $code  自定义数字验证码
     *
     * @return bool
     */
    public static function send($phone, $code)
    {
        $res = file_get_contents("http://sms.tehir.cn/code/sms/api/v1/send?srcSeqId=123&account=13580104633&password=xiasiwo&mobile=$phone&code=$code&time=2");
        if ("成功" === json_decode($res, true)["responseInfo"]) {
            return true;
        } else {
            return false;
        }
    }
}