<?php
/*
 * Created by PhpStorm.
 * User: zeffee
 * Date: 2016/8/8
 * Time: 13:35
 */

namespace App\Controllers;

use App\Lib\Authorization;
use App\Lib\Mail;
use App\Lib\Response;
use App\Lib\Html;
use App\Models\Post;
use App\Models\Bbs;
use App\Lib\SphinxClient;

class Tribune
{
    public $middle = [
//        "publish"      => ["Check_login", "Check_Operation_Count"],
//        "response"     => ["Check_login", "Check_Operation_Count"],
//        "getPublished" => "Check_login",
//        "getUnReadNum" => "Check_login",
//        "searchPost"   => ["Check_login", "Check_Operation_Count"],
//        "deletePost"   => ["Check_login", "Tribune_delete"],
//        "deleteBbs"    => ["Check_login", "Tribune_delete"]
    ];

    public static $status = [
        700 => "Unavailable Key",
        701 => "Unavailable ID",
        702 => "Unavailable Department",
        703 => "Out Of Index"
    ];


    /**论坛-首页加载-获取帖子数据
     *
     * @param string $department 部门(编程、前端、设计、文秘)
     * @param int    $page       页码
     *
     * @return post.指定页的帖子数据 page_count.页码总数 publish_key.发布帖子的key
     */
    public function index($department, $page = 1)
    {
        $data = [];

        $page_size = 7;

        $data["page_count"] = ceil(Post::leftJoin('user', 'user.id', '=', 'post.uid')
                ->whereIn("post.department", [$department, "全部"])
                ->count("count")
                ->select("")[0]["count"] / $page_size);

        $data["post"] = Post::leftJoin('user', 'user.id', '=', 'post.uid')
            ->whereIn("post.department", [$department, "全部"])
            ->orderBy("post.time desc")
            ->limit(($page - 1) * $page_size, $page_size)
            ->select('user.nickname,user.photo,post.id,post.title,post.short_content,post.time,post.last_time,post.view,post.response,post.img');

        $data["publish_key"] = $this->setCsrfKey("publish_key");

        Response::out(200, $data);
    }


    /**论坛-发帖
     *
     * @param string $title       标题
     * @param string $content     内容
     * @param string $department  部门(编程、前端、设计、文秘)
     * @param string $publish_key 发布帖子的key
     *
     * @return post_id.返回插入的id
     */
    public function publish($title, $content, $department, $publish_key)
    {
        $this->checkKey($publish_key, "publish_key");

        if (!in_array($department, ["编程", "前端", "设计", "文秘"])) {
            Response::out(702);

            die();
        }

        //管理员发布全部门帖子
        if (Authorization::isAuthorized(Session::get("user.role"), "manage_news")) {
            $department = "全部";
        }

        $time = date("Y-m-d H:i:s");
        $post_id = Post::insert([
            "uid"           => Session::get("user.id"),
            "title"         => Html::removeSpecialChars($title),
            "content"       => Html::removeXss($content),
            "department"    => $department,
            "time"          => $time,
            "last_time"     => $time,
            "short_content" => substr(Html::removeSpecialChars($content), 0, 60),
            "img"           => Html::getFirstImg($content)
        ]);

        Response::out(200, ["post_id" => $post_id]);
    }


    /**论坛-发表回复
     *
     * @param int    $pid          回复一楼的填写帖子id,否则填写0
     * @param int    $bid          回复其他楼层的填写楼层id,否则填写0
     * @param string $content      回复内容
     * @param string $response_key 回复帖子的key
     *
     * @return floor.返回回复的楼层
     */
    public function response($pid = 0, $bid = 0, $content, $response_key)
    {
        $this->checkKey($response_key, "response_key");

        //获取被回复帖子的数据
        $data = "";
        if ($pid > 0) {
            $data = Post::where("id", "=", $pid)->select("id pid,response,uid");
        } elseif ($bid > 0) {
            $data = Bbs::leftJoin("post", "post.id", "=", "bbs.pid")
                ->leftJoin("user", "user.id", "=", "bbs.uid")
                ->where("bbs.id", "=", $bid)
                ->select("user.nickname,post.id pid,post.response,post.uid puid,bbs.*");
        }

        if (isset($data[0])) {
            $data = $data[0];

            $time = date("Y-m-d H:i:s");
            Bbs::insert([
                "floor"          => $data["response"] + 2,
                "pid"            => $data["pid"],
                "uid"            => Session::get("user.id"),
                "content"        => Html::removeSpecialChars($content),
                "time"           => $time,
                "point_uid"      => $data["uid"],
                "point_floor"    => isset($data["floor"]) ? $data["floor"] : 1,
                "master_uid"     => isset($data["puid"]) ? $data["puid"] : $data["uid"],
                "point_nickname" => isset($data["nickname"]) ? $data["nickname"] : NULL
            ]);

            //回复+1
            Post::where("id", "=", $data["pid"])->increment("response");
            //更新最后回复时间
            Post::where("id", "=", $data["pid"])->update(["last_time" => $time]);

            Response::out(200, ["floor" => $data["response"] + 2]);
        } else {
            Response::out(701);
        }
    }


    /* 检查发帖、回复的key合法性
     *
     * @param $key
     * @param $column
     */
    private function checkKey($key, $column)
    {
        if (!Session::get($column) || $key != Session::get($column)) {
            Session::remove($column);
            Response::out(700);

            die();
        }
    }


    /**论坛-打开某个帖子-获取信息
     *
     * @param int $pid  1 帖子的id
     * @param int $page 0 回复的页数,默认值为1
     *
     * @return first.楼主信息,仅加载第一页返回 bbs.其他楼层信息 response_key.回复帖子的key
     */
    public function postInfo($pid, $page = 1)
    {
        $data = [];
        $page_size = 7;

        if (1 == $page) {
            Post::where("id", "=", $pid)->increment("view");

            $data["first"] = Post::leftJoin('user', 'user.id', '=', 'post.uid')
                ->where("post.id", "=", $pid)
                ->select('user.nickname,user.photo,post.*');
        }

        $data["bbs"] = Bbs::leftJoin('user', 'user.id', '=', 'bbs.uid')
            ->leftJoin('role', 'role.id', '=', 'user.role')
            ->where("bbs.pid", "=", $pid)
            ->limit(($page - 1) * $page_size, $page_size)
            ->select('role.name role,user.nickname,user.photo,bbs.*');

        $data["page_count"] = ceil(Bbs::where("pid", "=", $pid)->count("count")->select("")[0]["count"] / $page_size);

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
     * @return post.符合条件的帖子
     */
    public function searchPost($key)
    {
        $sphinx = new SphinxClient();
        $sphinx->SetServer("sphinx", 9312);
        $sphinx->SetMatchMode(SPH_MATCH_ANY);

        $post = $this->sphinxSearch($sphinx, $key, "post", "*");
//        $bbs = $this->sphinxSearch($sphinx, $key, "bbs", "bbs");

        Response::out(200, ["post" => $post]);
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

        Response::out(200);
    }


    /**论坛-删除回复
     *
     * @param int $bid 回复的id
     */
    public function deleteBbs($bid)
    {
        Bbs::where('id', '=', $bid)->delete();

        Response::out(200);
    }


    public function redis()
    {
        Cache::set("a", time());
        Response::out(200, Cache::get("a"));
    }

    public function test()
    {
        Mail::to("me@zeffee.com")->title("test")->content("testest");
    }

    public function update()
    {
        echo Html::getFirstImg("<b>asdfasdf</b>22< Javascript />aa" . "<a href=\"#\" onclick=\"alert(1);\">aaaaaaaaa</a>javascript<P><IMG SRC=javascript:alert('XSS')><javascript>alert('a')</javascript><IMG src=\"abc.jpg\"><IMG><P>Test</P><IMG src=\"abcdddd.jpg\"><IMG>");
    }
}