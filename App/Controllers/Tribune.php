<?php
/*
 * Created by PhpStorm.
 * User: zeffee
 * Date: 2016/8/8
 * Time: 13:35
 */

namespace App\Controllers;

use App\Lib\Response;
use App\Models\Post;
use App\Models\Bbs;
use App\Lib\SphinxClient;

class Tribune
{
    public $middle = [
        //checkip
        "publish"      => "check_login",
        "response"     => "check_login",
        "getPublished" => "check_login",
        "getUnReadNum" => "check_login",
        "searchPost"   => "check_login",
        "deletePost"   => "check_login",
        "deleteBbs"    => "check_login"
    ];

    public static $status = [
        700 => "Unavailable Key"
    ];


    /**论坛-首页加载-获取帖子数据
     *
     * @param int $page 页码
     *
     * @return post.指定页的帖子数据 publish_key.发布帖子的key
     */
    public function index($page = 1)
    {
        $data = [];
        $data["post"] = Post::leftJoin('user', 'user.id', '=', 'post.uid')
            ->orderBy("post.time desc")
            ->limit(($page - 1) * 10, 10)
            ->select('user.nickname,user.mail,user.role,user.photo,post.*');

        $data["publish_key"] = $this->setCsrfKey("publish_key");

        Response::out(200, $data);
    }


    /**论坛-发帖
     *
     * @param string $title       标题
     * @param string $content     内容
     * @param string $classify    分类
     * @param string $publish_key 发布帖子的key
     *
     * @return post_id.返回插入的id,仅在200状态码返回
     */
    public function publish($title, $content, $classify, $publish_key)
    {
        if (!Session::get("publish_key") || $publish_key != Session::get("publish_key")) {
            Session::remove("publish_key");

            Response::out(700);
        } else {
            $post_id = Post::insert([
                "uid"      => 1,
                "title"    => $title,
                "content"  => $content,
                "classify" => $classify,
                "time"     => date("Y-m-d H:i:s")
            ]);

            Response::out(200, ["post_id" => $post_id, "publish_key" => $this->setCsrfKey("publish_key")]);
        }
    }


    /**论坛-发表回复
     *
     * @param int    $pid          1 所属帖子的id
     * @param string $content      1 回复内容
     * @param int    $pointuid     1 回复指定楼层的层主id
     * @param int    $pointfloor   1 回复指定楼层的楼层数,回复一楼则不填
     * @param int    $masteruid    1 楼主的uid
     * @param string $response_key 1 楼主的uid
     *
     * @return floor.返回回复的楼层,仅在200状态码返回
     */
    public function response($pid, $content, $pointuid, $pointfloor, $masteruid, $response_key)
    {
        if (!Session::get("response_key") || $response_key != Session::get("response_key")) {
            Session::remove("response_key");

            Response::out(700);
        } else {
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

            Response::out(200, ["floor" => $floor, "response_key" => $this->setCsrfKey("response_key")]);
        }
    }


    /**论坛-打开某个帖子-获取信息
     *
     * @param int $pid 1 帖子的id
     * @param int $pid 0 回复的页数,默认值为1
     *
     * @return first.楼主信息,仅加载第一页返回 bbs.其他楼层信息
     */
    public function postInfo($pid, $page = 1)
    {
        $data = [];

        if (1 === $page) {
            $data["first"] = Post::leftJoin('user', 'user.id', '=', 'post.uid')->where("post.id", "=", $pid)->select('user.nickname,user.mail,user.role,user.photo,post.*');
        }
        $data["bbs"] = Bbs::leftJoin('user', 'user.id', '=', 'bbs.uid')->where("bbs.pid", "=", $pid)->limit(($page - 1) * 8, 8)->select('user.nickname,user.mail,user.role,user.photo,bbs.*');
        $data["response_key"] = $this->setCsrfKey("response_key");

        Response::out(200, $data);
    }


    /* 设置csrf密钥
     *
     * @param $name
     *
     * @return string
     */
    private function setCsrfKey($name)
    {
        $key = md5(time());
        Session::set($name, $key);

        return $key;
    }


    /**论坛-查看个人中心-获取曾经发布的帖子和回复
     *
     * @param int $uid 0 用户id,默认值为登录者uid
     *
     * @return post.帖子信息 bbs.被回复的信息,访问自己个人中心才有 unread.未读信息,访问自己个人中心才有
     */
    public function getPublished($uid = -1)
    {
        $data = [];

        if (-1 === $uid) {
            $uid = 2;

            if ($data['unread'] = $this->getUnReadMsg($uid)) {
                Bbs::where('point_uid', '=', $uid)->update(["is_read" => 1]);
                Bbs::where('master_uid', '=', $uid)->update(["master_read" => 1]);
            } else {
                $data['bbs'] = Bbs::leftJoin("user", "user.id", "=", "bbs.uid")->where("bbs.point_uid", "=", $uid)->orderBy('time desc')->limit(0, 10)->select('user.nickname,user.mail,user.role,user.photo,bbs.*');
            }
        }
        $data['post'] = Post::where("post.uid", "=", $uid)->orderBy('time desc')->limit(0, 5)->select();

        Response::out(200, $data);
    }


    /**论坛-获取未读信息条数
     *
     * @return num.未读信息条数
     */
    public function getUnReadNum()
    {
        Response::out(200, ["num" => count($this->getUnReadMsg(2))]);
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


    /**论坛-搜索指定内容的帖子或回复
     *
     * @param string $key 关键字
     *
     * @return post.符合条件的帖子 bbs.符合条件的回复
     */
    public function searchPost($key)
    {
        $sphinx = new SphinxClient();
        $sphinx->SetServer("localhost", 9312);
        $sphinx->SetMatchMode(SPH_MATCH_ANY);

        $post = $this->sphinxSearch($sphinx, $key, "post", "main");
        $bbs = $this->sphinxSearch($sphinx, $key, "bbs", "bbs");

        Response::out(200, ["post" => $post, "bbs" => $bbs]);
    }


    /* 搜索关键字
     *
     * @param $sphinx
     * @param $key
     * @param $table
     * @param $index
     *
     * @return mixed
     */
    private function sphinxSearch(&$sphinx, $key, $table, $index)
    {
        $search = $sphinx->Query($key, $index);
        if (isset($search["matches"])) {
            $match_id = array_keys($search["matches"]);

            switch ($table) {
                //no break at all
                case "post":
                    return Post::whereIn('id', $match_id)->orderBy('field(id,' . implode(",", $match_id) . ')')->select();
                case "bbs":
                    return Bbs::leftJoin("user", "user.id", "=", "bbs.uid")->whereIn('bbs.id', $match_id)->orderBy('field(bbs.id,' . implode(",", $match_id) . ')')->select("user.nickname,user.mail,user.role,user.photo,bbs.*");
            }
        }
    }


    /**论坛-删除帖子
     *
     * @param int $pid 帖子的id
     */
    public function deletePost($pid)
    {
        Post::where('id', '=', $pid)->delete();
        Bbs::where('pid', '=', $pid)->delete();
    }


    /**论坛-删除回复
     *
     * @param int $bid 回复的id
     */
    public function deleteBbs($bid)
    {
        Bbs::where('id', '=', $bid)->delete();
    }


    public function test()
    {
        $v = new \App\Lib\Vcode('img', 2, 16, 200, 200, false, true, 30, 0, __ROOT__ . "/public/" . mt_rand(1, 6) . ".jpg");
        Session::set("code", $v->getData());
        $v->show();
    }

    public function a()
    {
        response(Session::get("code")["type"]);
    }

    public function b($text)
    {
        $v = Session::get("code")["text"];
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

        Response::out(200);
    }

    public function redis()
    {
        Cache::set("a", time());
        Response::out(200, Cache::get("a"));
    }
}