<?php

/**
 * Created by PhpStorm.
 * User: zeffee
 * Date: 2016/8/8
 * Time: 13:46
 */

require "../Zereri/Lib/Test.php";

use Zereri\Lib\Test;

class UserTest extends PHPUnit_Framework_TestCase
{
    public function testPublish()
    {
        $data = [
            "title"    => "zeffee",
            "content"  => "he is a handsome boy!",
            "classify" => "编程部"
        ];

        $resp = Test::curl("http://localhost:80/wangyuan/public/Tribune/publish", json_encode($data));

        $this->assertArrayHasKey('status', json_decode($resp["result"], true));
    }
}