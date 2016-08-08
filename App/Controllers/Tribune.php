<?php
/*
 * Created by PhpStorm.
 * User: zeffee
 * Date: 2016/8/8
 * Time: 13:35
 */

namespace App\Controllers;

use App\Models\Post;
use App\Models\Bbs;

class Tribune
{
    /**首页加载-获取帖子数据
     *
     * @param int $page 页码
     *
     * @return data.指定页的帖子数据
     */
    public function index($page)
    {
        $data = Post::leftJoin('user', 'user.id', '=', 'post.uid')
            ->orderBy("post.time desc")
            ->limit(($page - 1) * 10, 10)
            ->select('user.nickname,post.*');

        response(["data" => $data]);
    }


    /**发帖
     *
     * @param string $title    标题
     * @param string $content  内容
     * @param string $classify 分类
     *
     * @return status.返回插入的id
     */
    public function publish($title, $content, $classify)
    {
        $post_id = Post::insert([
            "uid"      => 1,
            "title"    => $title,
            "content"  => $content,
            "classify" => $classify,
            "time"     => date("Y-m-d H:i:s")
        ]);

        response(["post_id" => $post_id]);
    }


    /**发表回复
     *
     * @param int    $pid        1 所属帖子的id
     * @param string $content    1 回复内容
     * @param int    $pointuid   0 回复指定楼层的层主id,默认值为-1
     * @param int    $pointfloor 0 回复指定楼层的楼层数,默认值为-1
     */
    public function response($pid, $content, $pointuid = -1, $pointfloor = -1)
    {
        $floor = Bbs::where("pid", "=", 28)->count("sum")->select()[0]["sum"] + 1;

        Bbs::insert([
            "floor"       => $floor,
            "pid"         => $pid,
            "uid"         => 1,
            "content"     => $content,
            "time"        => date("Y-m-d H:i:s"),
            "point_uid"   => $pointuid,
            "point_floor" => $pointfloor
        ]);

        response(["floor" => $floor]);
    }


    /**打开某个帖子-获取信息
     *
     * @param int $pid 帖子的id
     */
    public function postInfo($pid)
    {

    }


    public function test()
    {
        $res = Bbs::where("pid", "=", 28)->count("sum")->select()[0]["sum"];
        echo "<pre>";
        print_r($res);
        echo "</pre>";
    }
}