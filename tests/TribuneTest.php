<?php

/**
 * Created by PhpStorm.
 * User: zeffee
 * Date: 2016/8/8
 * Time: 13:46
 */

require "../Zereri/Lib/Test.php";

use Zereri\Lib\Test;

class TribuneTest extends PHPUnit_Framework_TestCase
{

    public function testPublish()
    {
        $data = [
            "title"    => "zeffee",
            "content"  => "he is a handsome boy!",
            "classify" => "编程部"
        ];

        $resp = Test::curl("http://localhost:80/wangyuan/public/Tribune/publish", json_encode($data));

        $res_data = json_decode($resp["result"], true);

        $this->assertNotEmpty($res_data['post_id']);
    }


    public function testGetPost()
    {
        $data = ["page" => 1];

        $resp = Test::curl("http://localhost:80/wangyuan/public/Tribune/index", json_encode($data));

        $res_data = json_decode($resp["result"], true);

        $this->assertNotEmpty($res_data["data"]);
    }


    public function testResponse()
    {
        $data = [
            "pid"     => 1,
            "content" => "Zeffee is a handsome boy!",
        ];

        $resp = Test::curl("http://localhost:80/wangyuan/public/Tribune/response", json_encode($data));

        $res_data = json_decode($resp["result"], true);

        $this->assertNotEmpty($res_data['floor']);
    }
}