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
use App\Lib\SphinxClient;

class Tribune
{
    /**论坛-首页加载-获取帖子数据
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
            ->select('user.nickname,user.mail,user.role,user.photo,post.*');

        response(["data" => $data]);
    }


    /**论坛-发帖
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


    /**论坛-发表回复
     *
     * @param int    $pid        1 所属帖子的id
     * @param string $content    1 回复内容
     * @param int    $pointuid   1 回复指定楼层的层主id
     * @param int    $pointfloor 1 回复指定楼层的楼层数,回复一楼则不填
     * @param int    $masteruid  1 楼主的uid
     */
    public function response($pid, $content, $pointuid, $pointfloor, $masteruid)
    {
        $floor = Bbs::where("pid", "=", $pid)->count("sum")->select()[0]["sum"] + 2;

        Bbs::insert([
            "floor"       => $floor,
            "pid"         => $pid,
            "uid"         => 1,
            "content"     => $content,
            "time"        => date("Y-m-d H:i:s"),
            "point_uid"   => $pointuid,
            "point_floor" => $pointfloor,
            "master_uid"  => $masteruid
        ]);

        response(["floor" => $floor]);
    }


    /**论坛-打开某个帖子-获取信息
     *
     * @param int $pid 1 帖子的id
     * @param int $pid 0 回复的页数,默认值为1
     *
     * @return first.楼主信息 bbs.其他楼层信息
     */
    public function postInfo($pid, $page = 1)
    {
        $data = [];

        if (1 === $page) {
            $data["first"] = Post::leftJoin('user', 'user.id', '=', 'post.uid')->where("post.id", "=", $pid)->select('user.nickname,user.mail,user.role,user.photo,post.*');
        }
        $data["bbs"] = Bbs::leftJoin('user', 'user.id', '=', 'bbs.uid')->where("bbs.pid", "=", $pid)->limit(($page - 1) * 8, 8)->select('user.nickname,user.mail,user.role,user.photo,bbs.*');

        response($data);
    }


    /**论坛-查看个人中心-获取曾经发布的帖子和回复
     *
     * @param int $uid 0 用户id,默认值为登录者uid
     *
     * @return post.帖子信息 bbs.回复信息
     */
    public function getPublished($uid = -1)
    {
        $data = [];

        if (-1 === $uid) {
            $uid = 2;

            $data['unread'] = $this->getUnReadMsg($uid);

            Bbs::where('point_uid', '=', $uid)->update([
                "is_read" => 1
            ]);
            Bbs::where('master_uid', '=', $uid)->update([
                "master_read" => 1
            ]);
        }
        $data['post'] = Post::where("post.uid", "=", $uid)->orderBy('time desc')->limit(0, 5)->select();
        //$data['bbs'] = Bbs::where("bbs.uid", "=", $uid)->orderBy('time desc')->limit(0, 5)->select();

        response($data);
    }


    /**获取未读信息条数
     *
     */
    public function getUnReadNum()
    {
        response(["num" => count($this->getUnReadMsg(2))]);
    }


    /* 获取指定用户的未读信息
     *
     * @param $uid
     *
     * @return mixed
     */
    private function getUnReadMsg($uid)
    {
        return Bbs::raw(
            '(select bbs.*,author.nickname author_nickname,author.photo author_photo,point.nickname point_nickname,point.photo point_photo from bbs left join user author on author.id=bbs.uid left join user point on point.id=bbs.point_uid where point_uid=? and is_read=0 and uid<>?) union (select bbs.*,author.nickname author_nickname,author.photo author_photo,point.nickname point_nickname,point.photo point_photo from bbs left join user author on author.id=bbs.uid left join user point on point.id=bbs.point_uid where master_uid=? and master_read=0 and uid<>?) order by time desc',
            [$uid, $uid, $uid, $uid]
        );
    }


    /**搜索指定内容的帖子
     *
     * @param string $key 关键字
     *
     * @return data.帖子内容
     */
    public function searchPost($key)
    {
        $sphinx = new SphinxClient();
        $sphinx->SetServer("localhost", 9312);
        $sphinx->SetMatchMode(SPH_MATCH_ANY);
        $search = $sphinx->Query($key, "*");
        $data = Post::whereIn('id', array_keys($search["matches"]))->select();

        response(["data" => $data]);
    }


    public function test()
    {
        $res = Bbs::raw('(select bbs.*,author.nickname author_nickname,author.photo author_photo,point.nickname point_nickname,point.photo point_photo from bbs left join user author on author.id=bbs.uid left join user point on point.id=bbs.point_uid where point_uid=? and is_read=0 and uid<>?) union (select bbs.*,author.nickname author_nickname,author.photo author_photo,point.nickname point_nickname,point.photo point_photo from bbs left join user author on author.id=bbs.uid left join user point on point.id=bbs.point_uid where master_uid=? and master_read=0 and uid<>?) order by time desc', []);
        echo "<pre>";
        print_r($res);
        echo "</pre>";
    }
}