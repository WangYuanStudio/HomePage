<?php
namespace App\Controllers;

class Example
{
    /**样本测试
     *
     * @return json name.Zereri
     */
    public function test()
    {
    	$res = ["aaa"];
        response(["name" => $res]);
    }
}