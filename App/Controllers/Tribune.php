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
use App\Models\User;
use App\Lib\SphinxClient;
use PHPExcel;

class Tribune
{
    public $middle = [
//        "publish"      => ["Check_login", "Check_Operation_Count"],
//        "response"     => ["Check_login", "Check_Operation_Count"],
//        "getPublished" => "Check_login",
//        "getMsg" => "Check_login",
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

        //获取被回复帖子或楼层的数据
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
            //添加消息列表
            if (isset($data["puid"]) && $data["puid"] !== $data["uid"]) {
                //回复其他楼层
                Common::setInform($data["puid"], "论坛消息", "收到回复", "您的帖子收到一条回复，请点击查看！", "wangyuan.info");
                Common::setInform($data["uid"], "论坛消息", "收到回复", "您收到一条回复，请点击查看！", "wangyuan.info");
            } else {
                //回复楼主
                Common::setInform($data["uid"], "论坛消息", "收到回复", "您的帖子收到一条回复，请点击查看！", "wangyuan.info");
            }

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


    /**论坛-个人中心-历史帖子
     *
     * @param int $page 0 页码，默认第一页
     * @param int $uid  0 用户id,默认值为登录者uid
     *
     * @return post.帖子信息
     */
    public function getPublished($page = 1, $uid = -1)
    {
        $uid = $this->getUid($uid);
        $page_size = 5;

        $data = Post::where("uid", "=", $uid)
            ->orderBy("time desc")
            ->limit(($page - 1) * $page_size, $page_size)
            ->select();

        $page_count = ceil(Post::where("uid", "=", $uid)
                ->count("count")
                ->select("")[0]["count"] / $page_size);

        Response::out(200, ["post" => $data, "page_count" => $page_count]);
    }


    /**论坛-我的消息-获取登录者的被回复信息
     *
     * @param int $page 0 页码，默认第一页
     *
     * @return bbs.被回复的消息 page_count.总页数
     */
    public function getMsg($page = 1)
    {
        $uid = Session::get("user.id");
        $page_size = 6;

        $data = Bbs::leftJoin("user author", "author.id", "=", "bbs.uid")
            ->leftJoin("user point", "point.id", "=", "bbs.point_uid")
            ->where("bbs.uid", "<>", $uid)
            ->_and()
            ->whereOrWhere(["bbs.point_uid", "=", $uid], ["bbs.master_uid", "=", $uid])
            ->orderBy("bbs.time desc")
            ->limit(($page - 1) * $page_size, $page_size)
            ->select("bbs.*,author.nickname author_nickname,author.photo author_photo,point.nickname point_nickname,point.photo point_photo");

        $page_count = ceil(Bbs::where("uid", "<>", $uid)
                ->_and()
                ->whereOrWhere(["point_uid", "=", $uid], ["master_uid", "=", $uid])
                ->count("count")
                ->select("")[0]["count"] / $page_size);

        Response::out(200, ["bbs" => $data, "page_count" => $page_count]);
    }


    /**论坛-在指定用户的帖子里进行搜索
     *
     * @param string $key  关键字
     * @param int    $page 页码，默认第一页
     * @param int    $uid  用户id
     *
     * @return post.符合条件的帖子 page_count.总页数
     */
    public function searchUserPost($key, $page = 1, $uid = -1)
    {
        $uid = $this->getUid($uid);
        $match_id = $this->sphinxSearch($key, "*");
        $page_size = 5;

        $post = Post::whereIn('id', $match_id)
            ->andWhere("uid", "=", $uid)
            ->orderBy('field(id,' . implode(",", $match_id) . ')')
            ->limit(($page - 1) * $page_size, $page_size)
            ->select();

        $page_count = ceil(Post::whereIn('id', $match_id)
                ->andWhere("uid", "=", $uid)
                ->count("count")
                ->select("")[0]["count"] / $page_size);

        Response::out(200, ["post" => $post, "page_count" => $page_count]);
    }


    /* 获取uid
     *
     * @param $uid
     *
     * @return mixed
     */
    private function getUid($uid)
    {
        if (-1 === $uid) {
            $uid = Session::get("user.id");
        }

        return $uid;
    }


    /**论坛-搜索指定内容的帖子或回复
     *
     * @param string $key  关键字
     * @param int    $page 页码，默认第一页
     *
     * @return post.符合条件的帖子 page_count.总页数
     */
    public function searchPost($key, $page = 1)
    {
        $match_id = $this->sphinxSearch($key, "*");
        $page_size = 7;

        $post = Post::leftJoin('user', 'user.id', '=', 'post.uid')
            ->whereIn('post.id', $match_id)
            ->orderBy('field(post.id,' . implode(",", $match_id) . ')')
            ->limit(($page - 1) * $page_size, $page_size)
            ->select("user.nickname,user.photo,post.id,post.title,post.short_content,post.time,post.last_time,post.view,post.response,post.img");

        $page_count = ceil(Post::whereIn('id', $match_id)
                ->count("count")
                ->select("")[0]["count"] / $page_size);

        Response::out(200, ["post" => $post, "page_count" => $page_count]);
    }


    /* 搜索关键字
     *
     * @param $key
     * @param $table
     * @param $index
     *
     * @return mixed
     */
    private function sphinxSearch($key, $index)
    {
        $sphinx = new SphinxClient();
        $sphinx->SetServer("sphinx", 9312);
        $sphinx->SetMatchMode(SPH_MATCH_ANY);

        $search = $sphinx->Query($key, $index);
        if (isset($search["matches"])) {
            return array_keys($search["matches"]);
        }
    }


    /**论坛-获取用户的基本资料
     *
     * @param int $uid 用户id
     *
     * @return data.用户资料
     */
    public function getUserInfo($uid = -1)
    {
        $uid = $this->getUid($uid);
        $data = User::leftJoin("role", "role.id", "=", "user.role")->where("user.id", "=", $uid)->select("user.nickname,user.photo,role.name role")[0];

        Response::out(200, $data);
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
//        for($i=0;$i<5;$i++){
//            Mail::to("sostuts@vip.qq.com")->title("帅哥来嘛")->content("第 <b>$i 777</b> 遍！");
//            echo $i."  s<br>";
//            sleep(2);
//        }
//        $a=new \PHPExcel();
//        $w=new \PHPExcel_Writer_Excel2007($a);
//        $w->save("a.xlsx");
//        $w= new \PHPExcel_Writer_Excel5($a);
//        header("Pragma: public");
//        header("Expires: 0");
//        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
//        header("Content-Type:application/force-download");
//        header("Content-Type:application/vnd.ms-execl");
//        header("Content-Type:application/octet-stream");
//        header("Content-Type:application/download");;
//        header('Content-Disposition:attachment;filename="resume.xls"');
//        header("Content-Transfer-Encoding:binary");
//        $w->save('php://output');
        $a=new \PHPExcel_Reader_Excel5();
    }

    public function update()
    {
        Common::setInform(1, "论坛", "你收到一条回复", "www.wangyuan.info", "www.wangyuan.info");
    }
}