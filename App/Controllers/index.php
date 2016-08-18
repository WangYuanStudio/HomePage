<?php
namespace App\Controllers;
use App\Models\Member;
use App\Models\Message;
use App\Models\Article;

class Index
{
    /**官网—成员展示系
   *
   * @param string $department 部门名称
   * @return status.状态 errmsg.错误信息 data.二维数组包括该部门所有成员信息id,name,sex,photo,department,habbit,position,blog,phone,introduction
   */

    public function ShowMember($department)
    {
        $data=Member::where("department","=",$department)->select();
         
        if(sizeof($data)!=0)
        {
            $status=['status' => "200",'errmsg' => config("common_status")['200'],'data'=>$data];
            response( $status, "json");
        }
        else
        {
            $status=['status' => "601",'errmsg' => '暂无成员信息!','data'=>''];
            response($status, "json");
        }
    }
     
    /**官网—游客留言信息
   *
   *
   * @return status.状态 errmsg.错误信息 data.二维数组包括所有审核后的留言信息nickname,message,time,id
   */
    public function GetMessage()
    {
       $d=Message::leftJoin('user', 'message.uid', '=', 'user.id')->where("auth","=","1")->orderBy('time desc')->select('message.*,user.nickname');
       if(sizeof($d)!=0)
       {
            $status=['status' => "200",'errmsg' => config("common_status")['200'],'data'=>$d];
            response($status,"json");
       }
       else
       {
           $status=['status' => "602",'errmsg' => '暂无留言信息!','data'=>''];
           response($status,"json");
       }
    }

      /**官网—游客发表留言
   ** @param string $message 留言信息
    * @return status.状态 errmsg.错误信息
   */
    public function Send_message($message)
    {
      $str= Message::insert(['uid'=>Session::get("user.id"),'content' => $message, 'time' => $time, 'auth' => false]);
      if($str==0)
      {
         $status=["status" => "603",'errmsg' => '留言失败,请重试!','data'=>''];
         response($status,"json");
      }
      else
      {
         $status=["status" => "200",'errmsg' => '','data'=>''];
         response($status,"json");
      }
    }
     /**官网—管理员审核留言
   ** @param int $id 留言id
   * @return status.状态 errmsg.错误信息
   */
    public function check_mes($id)
    {
       $status=  Message::where('id','=',$id)->update(['auth' => true]);
       if($status==1)
       {
         $status=["status" => "200",'errmsg' => '','data'=>''];
         response( $status,"json");
       }
       else
       {
        $status=["status" => "604",'errmsg' => '审核失败,请重试！','data'=>''];
        response( $status,"json");
       }
    }

        /**官网—管理员删除留言
   ** @param int $id 留言id
   * @return status.状态 errmsg.错误信息
   */
    public function del_mes($id)
    {
      $status= Message::where('id', '=',$id)->delete();
      if($status==1)
      {
        $status=["status" => "200",'errmsg' => '','data'=>''];
        response( $status,"json");
      }
      else
      {
         $status=["status" => "605",'errmsg' => '留言删除失败','data'=>''];
         response( $status,"json");
      }
    }
   
}