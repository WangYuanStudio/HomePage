<?php
/*
 * Created by PhpStorm.
 * User: zeffee
 * Date: 2016/8/8
 * Time: 13:35
 */

namespace App\Controllers;

use App\Models\Post;

class Tribune
{
    /**获取帖子数据
     *
     * @param int $page 页码
     *
     * @return data.指定页的帖子数据
     */
    public function getPost()
    {
        $data = Post::leftJoin('user', 'user.id', '=', 'post.uid')->limit(1, 3)->select('user.nickname,post.*');

        echo "<pre>";
        print_r($data);
        echo "</pre>";

        response(["datas" => $data]);
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
        $insert_id = Post::insert([
            "uid"      => "1",
            "title"    => $title,
            "content"  => $content,
            "time"     => date("Y-m-d H:i:s"),
            "classify" => $classify
        ]);

        response(["status" => $insert_id]);
    }
}