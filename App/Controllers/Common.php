<?php
/*
 * Created by PhpStorm.
 * User: zeffee
 * Date: 2016/8/23
 * Time: 14:36
 */

namespace App\Controllers;

use App\Lib\Response;
use App\Models\Inform;
use App\Models\Role;
use App\Models\Role_Permission;
use App\Models\Permission;
use App\Models\User;

class Common
{
   public $middle = [
    'add_permission' =>['Check_login','Check_adminstrator'], 
    'add_role' =>['Check_login','Check_adminstrator'], 
    'del_permission' =>['Check_login','Check_adminstrator'], 
    'del_role' =>['Check_login','Check_adminstrator'], 
    'takeback_permission' =>['Check_login','Check_adminstrator'], 
    'assign_permission' =>['Check_login','Check_adminstrator'], 
    'update_userRole' =>['Check_login','Check_adminstrator']
  ];
    /**公共类-获取验证码图片
     *
     */
    public function verifyImg()
    {
        $v = new \App\Lib\Vcode('img', 2, 18, 150, 250, false, true, 0, 0, __ROOT__ . "/public/" . mt_rand(1, 19) . ".jpg", __ROOT__ . '/msyhbd.ttc', [255, 250, 250]);
        Cache::set($_SERVER["REMOTE_ADDR"] . "_vcode", $v->getData());
        $v->show();
    }


    /**公共类-获取验证码的提示
     *
     */
    public function verifyType()
    {
        response(200, Cache::get($_SERVER["REMOTE_ADDR"] . "_vcode")["type"]);
    }


    /**公共类-验证验证码
     *
     * @param array $text 验证码文本
     *
     * @return status.状态码
     */
    public function verify($text)
    {
        if ($v = Cache::get($_SERVER["REMOTE_ADDR"] . "_vcode")["text"]) {
            Cache::delete($_SERVER["REMOTE_ADDR"] . "_vcode");
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

            //删除记录次数
            Cache::delete($_SERVER["REMOTE_ADDR"] . "_count");
            //标记验证过关
            Cache::set($_SERVER["REMOTE_ADDR"] . "_verify_auth", 1);

            Response::out(200);
        } else {
            Response::out(302);
        }
    }


    /* 添加用户通知
     *
     * @param int    $uid      用户id
     * @param string $classify 分类(论坛，报名，作业，官网)
     * @param string $title    标题
     * @param string $content  通知的内容(简述)
     * @param string $url      跳转地址
     *
     * @return bool
     */
    public static function setInform($uid, $classify, $title, $content, $url)
    {
        Inform::insert([
            "uid"      => $uid,
            "classify" => $classify,
            "content"  => $content,
            "title"    => $title,
            "url"      => $url,
            "time"     => date("Y-m-d H:i:s")
        ]);

        return true;
    }


    /**获取我的通知列表
     *
     */
    public function getInform()
    {
        if ($data = Inform::whereAndWhere(["uid", "=", Session::get("user.id")], ["is_read", "=", 0])
            ->orderBy("time desc")
            ->select()
        ) {
            Inform::whereAndWhere(["uid", "=", Session::get("user.id")], ["is_read", "=", 0])
                ->update([
                    "is_read" => 1
                ]);
        }

        Response::out(200, $data);
    }


    /**获取我的通知数目
     *
     */
    public function getInformNum()
    {
        $num = Inform::whereAndWhere(["uid", "=", Session::get("user.id")], ["is_read", "=", 0])
            ->count("count")
            ->select("")[0]["count"];

        Response::out(200, ["num" => $num]);
    }

    /**公共类-添加权限
     *
     * @param string $name 权限的名称(英文)
     * @param string $description    权限的中文描述
     * @return status.状态 errmsg.错误信息
     */
    public function add_permission($name,$description)
    {
        if(Session::get("user.role")!=1)
        {
             Response::out(301);
             return false;
        }
         $str=Permission::where("name","=",$name)->select();
         if(sizeof($str)==0)
         {
              $str1= Permission::insert(['name'=>$name,'description' => $description]);
              if($str1!=0)
              {
                  Response::out(200);
              }
         }
         else
         {
             Response::out(304);
         }
    }
    /**公共类-添加角色
     *
     * @param string $name 角色名称(英文)
     * @param string $description    角色的中文描述
     * @return status.状态 errmsg.错误信息
     */
    public function add_role($name,$description)
    {
        if(Session::get("user.role")!=1)
        {
             Response::out(301);
             return false;
        }
         $str= Role::where("name","=",$name)->select();
         if(sizeof($str)==0)
         {
              $str1= Role::insert(['name'=>$name,'description' => $description]);
              if($str1!=0)
              {
                  Response::out(200);
              }
         }
         else
         {
             Response::out(305);
         }
    }
    /** 公共类-查看权限
     *
     * @return status.状态 errmsg.错误信息 data.数组包括数据库中所有权限(id),(name)名称,(description)中文描述
     */
    public function show_permission()
    {
        $data=Permission::select();
        if(sizeof($data)!=0)
        {
            Response::out(200,$data);
        }
        else
        {
            Response::out(306);
        }
    }
    /** 公共类-查看角色
     *
     * @return status.状态 errmsg.错误信息 data.数组包括数据库中所有角色(id),(name)名称,(description)中文描述
     */
    public function show_role()
    {
        $data=role::select();
        if(sizeof($data)!=0)
        {
            Response::out(200,$data);
        }
        else
        {
            Response::out(307);
        }
    }
    /** 公共类-删除权限
     * @param string $id 权限id
     * @return status.状态 errmsg.错误信息
     */
    public function del_permission($id)
    {
        $str=Permission::where('id', '=',$id)->delete();
        Role_Permission::where('pid', '=',$id)->delete();
        if($str==1)
        {
              Response::out(200);
        }
        else
        {
             Response::out(308);
        }
    }
    /** 公共类-删除角色
     * @param string $id 角色id
     * @return status.状态 errmsg.错误信息
     */
    public function del_role($id)
    {
        $str=Role::where('id', '=',$id)->delete();
         Role_Permission::where('rid', '=',$id)->delete();
        if($str==1)
        {
              Response::out(200);
        }
        else
        {
             Response::out(308);
        }
    }
    /** 公共类-更改角色名称，描述
     * @param string $id 角色id
     * @param string $name 角色中文名称
     * @param string $description 角色描述
     * @return status.状态 errmsg.错误信息
     */
    public function update_role($id,$name,$description)
    {

        $statuss= role::where('id', '=',$id)->update(
        [
        'name'=> $name,
        'description' => $description
        ]);
        if($statuss==1)
        {
          Response::out(200);
        }
        else
        {
           Response::out(309);
        }
    }
    /** 公共类-更改权限名称描述
     * @param string $id 权限id
     * @param string $name 权限中文名称
     * @param string $description 权限描述
     * @return status.状态 errmsg.错误信息
     */
    public function update_permission($id,$name,$description)
    {

        $statuss= Permission::where('id', '=',$id)->update(
        [
        'name'=> $name,
        'description' => $description
        ]);
        if($statuss==1)
        {
          Response::out(200);
        }
        else
        {
           Response::out(309);
        }
    }
    /** 公共类-为角色设置权限
     * @param string $rid 角色的id
     * @param string $pid 权限的id
     * @return status.状态 errmsg.错误信息
     */
    public function assign_permission($rid,$pid)
    {
       $statuss= Role_Permission::where('rid', '=', $rid)->andWhere('pid', '=', $pid)->select();
       if(sizeof($statuss)==0)
       {
            Role_Permission::insert(['rid'=>$rid ,'pid' =>$pid]);
            Response::out(200);
       }
       else
       {
            Response::out(310);
       }
    }
    /** 公共类-删除角色的权限
     * @param string $rid 角色的id
     * @param string $pid 权限的id
     * @return status.状态 errmsg.错误信息
     */
    public function takeback_permission($rid,$pid)
    {
        $statuss= Role_Permission::where('rid', '=', $rid)->andWhere('pid', '=', $pid)->select();
       if(sizeof($statuss)!=0)
       {
           $str= Role_Permission::where('rid', '=',$rid)->andWhere('pid', '=', $pid)->delete();
             if($str==1)
             {
              Response::out(200);
             }
             else
             {
              Response::out(308);
             }
       }
    }
    /** 公共类-显示角色拥有的权限
     * @param string $rid 角色的id
     * @return status.状态 errmsg.错误信息 data.数组包括角色中所有权限(id),(name)名称,(description)中文描述
     */
    public function showRole_permission($rid)
    {
        $data= Role_Permission::Join('permission', 'role_permission.pid', '=', 'permission.id')->where('rid', '=', $rid)->select('permission.*');
        if(sizeof($data)!=0)
        {
            Response::out(200,$data);
        }
        else
        {
            Response::out(306);
        }
    }
    /** 公共类-改变用户角色
     * @param string $rid 角色的id
     * @param string $uid 用户的id
     * @return status.状态 errmsg.错误信息
     */
    public function update_userRole($rid,$uid)
    {
        $statuss= User::where('id', '=',$uid)->update(
        [
        'role' => $rid
        ]);
        if($statuss==1)
        {
            Response::out(200);
        }
        else
        {
            Response::out(309);
        }
    }


}