<?php
namespace App\Controllers;
use App\Models\Member;
use App\Models\Message;
use App\Models\Article;


class Index
{
  
  public $middle = [
   
    'Send_message' => 'Check_login',
    'check_mes' => ['Check_login','Check_Managermes'],
    'del_mes' => ['Check_login','Check_Managermes'] ,
    'edt_message' =>['Check_login','Check_Managermes'],
    'edt_article' => ['Check_login','Check_Managerarticle'],
    'del_article' => ['Check_login','Check_Managerarticle'] ,
    'publish_article' => ['Check_login','Check_Managerarticle'],
    'del_member' => ['Check_login','Check_ManagerMember'] ,
    'edt_member' =>['Check_login','Check_ManagerMember'] 
  ];
  /*错误代码 && 错误信息
   * 
   * @var array
   */
    public static $error = [
    601 => '暂无成员信息!',
    602 => '暂无留言信息!',
    603 => '留言失败,请重试!',
    604 => '审核失败,请重试!',
    605 => '删除失败',
    606 => '发布动态失败',
    607 => '暂无动态信息!',
    608 => '修改失败!'
    ];
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
            $status=['status' => "601",'errmsg' => Index::$error[601],'data'=>''];
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
           $status=['status' => "602",'errmsg' => Index::$error[602],'data'=>''];
           response($status,"json");
       }
    }

      /**官网—游客发表留言
   ** @param string $message 留言信息
    * @return status.状态 errmsg.错误信息
   */
    public function Send_message($message)
    {
      $time= date('y-m-d h:i:s',time());
      $str= Message::insert(['uid'=>Session::get("user.id"),'content' => $message, 'time' => $time, 'auth' => false]);
      if($str==0)
      {
         $status=["status" => "603",'errmsg' => Index::$error[603],'data'=>''];
         response($status,"json");
      }
      else
      {
         $status=["status" => "200",'errmsg' => config("common_status")['200'],'data'=>''];
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
         $status=["status" => "200",'errmsg' => config("common_status")['200'],'data'=>''];
         response( $status,"json");
       }
       else
       {
        $status=["status" => "604",'errmsg' =>  Index::$error[604],'data'=>''];
        response( $status,"json");
       }
    }

        /**官网—管理员删除留言
   ** @param int $id 留言id
   * @return status.状态 errmsg.错误信息
   */
    public function del_mes($id)
    {
      $statuss= Message::where('id', '=',$id)->delete();
      if($statuss==1)
      {
        $status=["status" => "200",'errmsg' => config("common_status")['200'],'data'=>''];
        response( $status,"json");
      }
      else
      {
         $status=["status" => "605",'errmsg' => Index::$error[605],'data'=>''];
         response( $status,"json");
      }
    }
      /**官网—发表网园动态
      *
   ** @param string $title 标题
    ** @param string $content 类容
    * @return status.状态 errmsg.错误信息
   */
    public function publish_article($title,$content)
    {
      $time= date('y-m-d h:i:s',time());
      $str= Article::insert(['title'=>$title,'content' => $content,'time'=>$time]);
      if($str==0)
      {
         $status=["status" => "606",'errmsg' => Index::$error[606],'data'=>''];
         response($status,"json");
      }
      else
      {
         $status=["status" => "200",'errmsg' => config("common_status")['200'],'data'=>''];
         response($status,"json");
      }
    }
        /**官网—删除网园动态
        *
   ** @param string $id 动态的id
    * @return status.状态 errmsg.错误信息
   */
    public function del_article($id)  
    {
      $statuss= Article::where('id', '=',$id)->delete();
      if($statuss==1)
      {
        $status=["status" => "200",'errmsg' => config("common_status")['200'],'data'=>''];
        response( $status,"json");
      }
      else
      {
         $status=["status" => "605",'errmsg' => Index::$error[605],'data'=>''];
         response( $status,"json");
      }
    }
        /**官网—获取网园动态信息
   *
   *
   * @return status.状态 errmsg.错误信息 data.二维数组包括所有动态title,content,time,id
   */
    public function get_article()
    {
       $d=Article::orderBy('time desc')->select();
       if(sizeof($d)!=0)
       {
            $status=['status' => "200",'errmsg' => config("common_status")['200'],'data'=>$d];
            response($status,"json");
       }
       else
       {
           $status=['status' => "607",'errmsg' => Index::$error[607],'data'=>''];
           response($status,"json");
       }
    }
      /**官网—修改网园动态
        *
   ** @param string $id 动态的id
   ** @param string $title 动态的标题
   ** @param string $content 动态的类容
    * @return status.状态 errmsg.错误信息
   */
    public function edt_article($id,$title,$content)
    {
        $statuss= Article::where('id', '=',$id)->update(['title' => $title, 'content' => $content]);
        if($statuss==1)
        {
            $status=['status' => "200",'errmsg' => config("common_status")['200'],'data'=>''];
            response($status,"json");
        }
        else
        {
           $status=['status' => "608",'errmsg' => Index::$error[608],'data'=>''];
           response($status,"json");
        }
    
    }
    /**官网—修改成员信息
        *
   ** @param string $id 成员的id
   ** @param string $name 成员姓名
    ** @param string $sex 成员性别
   ** @param string $departmentt 部门
      ** @param string $habbit 爱好
         ** @param string $position 职位
         ** @param string $blog 博客
          ** @param string $introduction 介绍
    * @return status.状态 errmsg.错误信息
   */
    public function edt_member($id,$name,$sex,$department,$habbit,$position,$blog,$introduction)
    {
       $statuss= Member::where('id', '=',$id)->update(
        ['name' => $name, 
        'sex' => $sex,
        'department'=>$department,
        'habbit'=>$habbit,
        'position'=>$position,
        'blog'=>$blog,
        'introduction'=>$introduction
        ]);
        if($statuss==1)
        {
            $status=['status' => "200",'errmsg' => config("common_status")['200'],'data'=>''];
            response($status,"json");
        }
        else
        {
           $status=['status' => "608",'errmsg' => Index::$error[608],'data'=>''];
           response($status,"json");
        }
    
    }
            /**官网—删除成员信息
        *
   ** @param string $id 成员的id
    * @return status.状态 errmsg.错误信息
   */
    public function del_member($id)
    {
      
        $statuss=Member::where('id', '=',$id)->delete();
      if($statuss==1)
      {
        $status=["status" => "200",'errmsg' => config("common_status")['200'],'data'=>''];
        response( $status,"json");
      }
      else
      {
         $status=["status" => "605",'errmsg' => Index::$error[605],'data'=>''];
         response( $status,"json");
      }
    
    }
                /**官网—修改留言信息
        *
   ** @param string $id 留言的id
    * @return status.状态 errmsg.错误信息
   */
    public function edt_message($id,$content)
    {
        $statuss= Message::where('id', '=',$id)->update(
        [
        'content'=>$content
        ]);
        if($statuss==1)
        {
            $status=['status' => "200",'errmsg' => config("common_status")['200'],'data'=>''];
            response($status,"json");
        }
        else
        {
           $status=['status' => "608",'errmsg' => Index::$error[608],'data'=>''];
           response($status,"json");
        }
    }
 



}