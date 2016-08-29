<?php
/**
 * Created by PhpStorm.
 * User: zeffee
 * Date: 2016/8/29
 * Time: 21:51
 */

namespace App\Lib;


class Html
{
    /**取第一张图片的路径
     *
     * @param $html
     *
     * @return mixed
     */
    public static function getFirstImg($html)
    {
        preg_match("/<img.*src=\"(.*)\"[^>]*>/Uism", $html, $img);

        return isset($img[1]) ? $img[1] : "";
    }


    /**转义特殊符号
     *
     * @param $html
     *
     * @return string
     */
    public static function removeSpecialChars($html)
    {
        return htmlspecialchars($html, ENT_QUOTES);
    }

    /**防xss
     *
     * @param $html
     *
     * @return mixed|string
     */
    public static function removeXss($html)
    {
        return (new XssHtml($html))->getHtml();
    }
}