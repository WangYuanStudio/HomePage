<?php
/**
 * Created by PhpStorm.
 * User: zeffee
 * Date: 2016/9/22
 * Time: 12:18
 */

namespace App\Lib;


class Session extends \Zereri\Lib\Session
{
    /**设置session值
     *
     * @param $key
     * @param $value
     *
     */
    public static function set($key, $value)
    {
        $session =& parent::getSession($key);
        $session = $value;
    }


    /**获取session值
     *
     * @param $key
     *
     * @return mixed
     */
    public static function get($key)
    {
        $value = $session =& parent::getSession($key);

        return $value;
    }
}