<?php
namespace App\Controllers;

class index
{
    /**官网成员展示系
   *
   * @param string $department  部门名称
   * @return status.状态/错误代码，$data成员信息包括id,name,sex,photo,department,habbit,position,blog,phone,introduction
   */
    public function ShowMember($department)
    {
        $d=TB('member')->where('department', '=', $department)->select();
        if(sizeof($d)!=0)
        {
            $data=$d;
            response($data, "json");

        }
        else
        {
            $status=["status" => "0",'msg' => '暂无成员信息'];
            response($status, "json");
        }
    }
     
      /**游客留言
   *
   * @return status.状态/错误代码，$data留言信息 包括nickname,message,time,id
   */
    public function GetMessage()
    {
       $d=TB('message')->leftJoin('user', 'message.uid', '=', 'user.id')->where("auth","=","1")->orderBy('time desc')->select('message.*,user.nickname');
       if(sizeof($d)!=0)
       {
          //  $name=TB("user")
            $data =$d;
            response($data,"json");
       }
       else
       {
           $status=['status'=>'0','msg' => '暂无留言'];
           response($status,"json");
       }
    }

      /**游客发表留言
   ** @param string $message 留言信息
   * @return status,msg 状态/返回信息，
   */
    public function Send_message($message)
    {
      $token = Session::get("user");
      $time=date('y-m-d h:i:s',time());
      if($token==null)
      {
        $str= TB('message')->insert(['content' => $message, 'time5' => $time, 'auth' => false]);
      }
      else
      {
       $str= TB('message')->insert(['uid'=>Session::get("user.id"),'content' => $message, 'time' => $time, 'auth' => false]);
      }
      if($str==0)
      {
         $status=["status" => "0",'msg' => '留言失败'];
         response($status,"json");
      }
      else
      {
          $status=["status" => "1",'msg' => '成功留言'];
         response($status,"json");
      }
    }
     /**管理员审核留言
   ** @param int $id 留言id
   * @return status,msg 状态/返回信息，
   */
    public function check_mes($id)
    {
       $status= TB('message')->where('id','=',$id)->update(['auth' => true]);
       if($status==1)
       {
         $status=["status" => "1",'msg' => '审核成功'];
         response( $status,"json");
       }
       else
       {
        $status=["status" => "0",'msg' => '审核失败'];
        response( $status,"json");
       }
    }

        /**管理员删除留言
   ** @param int $id 留言id
   * @return status,msg 状态/返回信息，
   */
    public function del_mes($id)
    {
      $status= TB('message')->where('id', '=',$id)->delete();
      if($status==1)
      {
        $status=["status" => "1",'msg' => '删除成功'];
        response( $status,"json");
      }
      else
      {
         $status=["status" => "0",'msg' => '删除失败'];
         response( $status,"json");
      }
    }
   
  

      
   
}