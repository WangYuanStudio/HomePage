<?php
namespace App\Controllers;
use App\Models\Member;
use App\Models\Message;
use App\Models\Article;
use App\Lib\Response;
use App\Lib\Document;


class Index
{
  
  public $middle = [
   
    'Send_message' => ['Check_login','Check_Operation_Count'],
    'check_mes' => ['Check_login','Check_Managermes'],
    'del_mes' => ['Check_login','Check_Managermes'] ,
    'edt_message' =>['Check_login','Check_Managermes'],
    'edt_article' => ['Check_login','Check_Managerarticle'],
    'del_article' => ['Check_login','Check_Managerarticle'] ,
    'publish_article' => ['Check_login','Check_Managerarticle','Check_Operation_Count'],
    'del_member' => ['Check_login','Check_ManagerMember'] ,
    'edt_member' =>['Check_login','Check_ManagerMember'] 
  ];
  /*错误代码 && 错误信息
   * 
   * @var array
   */
    public static $status = [
    601 => 'no member information',
    602 => 'no message!',
    603 => 'fail to leave a message',
    604 => 'fail to checked your message',
    605 => 'fail to delete',
    606 => 'fail to send a dynamic',
    607 => 'no dynamics',
    608 => 'fail to edit',
    609 => 'fail to insert'
    ];
  /**官网—成员展示系
   *
   * @param string $department 部门名称 (frontend,backend,secretary)
   * @return status.状态 errmsg.错误信息 data.二维数组包括该部门所有成员信息id,name,sex,photo,department,habbit,position,blog,phone,introduction
   */
    public function ShowMember($department,$page=1,$num)
    {
        $data=Member::where("department","=",$department)->limit(($page-1)*$num,$num)->select();
         
        if(sizeof($data)!=0)
        {
              Response::out(200,$data);
        }
        else
        {
            Response::out(601);
        }
    }
     
    /**官网—游客留言信息
   *
   *@param int $auto 留言状态(1,为未审核,2为审核,3为所有)
   *@param int $page 页数
   *@param int $num 每一页的数量
   * @return status.状态 errmsg.错误信息 data.二维数组包括所有留言信息nickname,message,time,id
   */
    public function GetMessage($auto,$page=1,$num)
    {
       $auto--;
       if($auto==0||$auto==1)
       {
       $d=Message::leftJoin('user', 'message.uid', '=', 'user.id')->where("auth","=",$auto)->orderBy('time desc')->limit(($page-1)*$num,$num)->select('message.*,user.nickname');
       }
       else if($auto==2)
       {
         $d=Message::leftJoin('user', 'message.uid', '=', 'user.id')->orderBy('time desc')->limit(($page-1)*$num,$num)->select('message.*,user.nickname');
       }
       else
       {
         Response::out(602);
         return false;
       }
       if(sizeof($d)!=0)
       {
           Response::out(200,$d);
       }
       else
       {
         Response::out(602);
       }
    }

  /**官网—游客发表留言(字符串长度<600)
   * @param string $message 留言信息
   * @return status.状态 errmsg.错误信息
   */
    public function Send_message($message)
    {
      $time= date('y-m-d H:i:s',time());
      $str= Message::insert(['uid'=>Session::get("user.id"),'content' => $message, 'time' => $time, 'auth' => 0]);
      if($str==0)
      {

        Response::out(603);
      }
      else
      {
            Response::out(200);
      }
    }
  /**官网—管理员审核留言
   **@param int $id 留言id
   * @return status.状态 errmsg.错误信息
   */
    public function check_mes($id)
    {
       $status=  Message::where('id','=',$id)->update(['auth' => '1']);
       if($status==1)
       {
        Response::out(200);
       }
       else
       {
       Response::out(604);
       }
    }

  /**官网—管理员删除留言
   * @param int $id 留言id
   * @return status.状态 errmsg.错误信息
   */
    public function del_mes($id)
    {
      $statuss= Message::where('id', '=',$id)->delete();
      if($statuss==1)
      {

         Response::out(200);
      }
      else
      {

         Response::out(605);
      }
    }
  /**官网—发表网园动态
    *
   ** @param string $title 标题(字符串长度<255)
    ** @param string $content 类容
    * @return status.状态 errmsg.错误信息
   */
    public function publish_article($title,$content)
    {
      $time= date('y-m-d H:i:s',time());
      $str= Article::insert(['title'=>$title,'content' => $content,'time'=>$time]);
      if($str==0)
      {
         Response::out(606);
      }
      else
      {
         Response::out(200);
      }
    }
  /**官网—删除网园动态
    *
    * @param string $id 动态的id
    * @return status.状态 errmsg.错误信息
    */
    public function del_article($id)  
    {
      $statuss= Article::where('id', '=',$id)->delete();
      if($statuss==1)
      {
         Response::out(200);
      }
      else
      {
          Response::out(605);
      }
    }
  /**官网—获取网园动态信息
   *@param int $page 页数
   *@param int $num 每一页的数量
   * @return status.状态 errmsg.错误信息 data.二维数组包括所有动态title,content,time,id
   */
    public function get_article($page=1,$num)
    {
       $d=Article::orderBy('time desc')->limit(($page-1)*$num,$num)->select();
       if(sizeof($d)!=0)
       {
       Response::out(200,$d);
       }
       else
       {
        Response::out(607);
       }
    }
  /**官网—修改网园动态
    *
   ** @param string $id 动态的id
   ** @param string $title 动态的标题(字符串长度<255)
   ** @param string $content 动态的类容
    * @return status.状态 errmsg.错误信息
   */
    public function edt_article($id,$title,$content)
    {
        $statuss= Article::where('id', '=',$id)->update(['title' => $title, 'content' => $content]);
        if($statuss==1)
        {
           Response::out(200);
        }
        else
        {

           Response::out(608);
        }
    
    }
 /*官网—修改成员信息
  ** @param string $id 成员id
  ** @param string $name 成员姓名 (长度<10)
  ** @param string $sex 成员性别
  ** @param string $departmentt 部门 
  ** @param string $habbit 爱好 (长度<255)
  ** @param string $position 职位
  ** @param string $blog 博客(地址)
  ** @param string $phone 电话 (长度<=11)
  ** @param string $introduction 介绍 (长度<255)
  * @return status.状态 errmsg.错误信息
   */
    public function edt_member($id,$name,$sex,$department,$habbit,$position,$blog,$phone,$introduction)
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
            Response::out(200);
        }
        else
        {
            Response::out(608);
        }
    
    }
   /**官网—删除成员信息
    *
    * @param string $id 成员的id
    * @return status.状态 errmsg.错误信息
    */
    public function del_member($id)
    {
      
        $statuss=Member::where('id', '=',$id)->delete();
      if($statuss==1)
      {

         Response::out(200);
      }
      else
      {
         Response::out(605);
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
          Response::out(200);
        }
        else
        {
           Response::out(608);
        }
    }




  /**官网—添加成员信息
   *
   ** @param string $form_name 头像上传组名(值为"no"默认头像)
   ** @param string $name 成员姓名 (长度<10)
   ** @param string $sex 成员性别
   ** @param string $departmentt 部门 (frontend,backend,secretary)
   ** @param string $habbit 爱好 (长度<255)
   ** @param string $position 职位
   ** @param string $blog 博客(地址)
   ** @param string $introduction 介绍 (长度<255)
   ** @param string $phone 电话 (长度<=11)
   * @return status.状态 errmsg.错误信息
   */
    public function add_member($form_name,$name,$sex,$department,$habbit,$position,$blog,$phone,$introduction)
    {
      $path='avatar/';
      if($form_name=="no")
      {
        $src="avatar/head.gif";
      }
      else
      {
        $src = Document::Upload($form_name, $path);
      }
      $str= Member::insert(['photo'=>$src,'name'=>$name,'sex' => $sex,'department'=>$department,'habbit'=>$habbit,'position'=>$position,'blog'=>$blog,'phone'=>$phone,'introduction'=>$introduction]);
      if($str==0)
      {
        Response::out(609);
      }
      else
      {
        Response::out(200);
      }
    }
  /**官网—修改成员头像
   * @param string $id 成员id
   * @param string $form_name  头像上传组名
   * @return status.状态 errmsg.错误信息
   */
    public function edt_member_pho($id,$form_name)
    {
      $path='avatar/';
      $src = Document::Upload($form_name, $path);
      $statuss= Member::where('id', '=',$id)->update(
        [
        'photo'=>$src
        ]);
        if($statuss==1)
        {
          Response::out(200);
        }
        else
        {
           Response::out(608);
        }
    }
    
    

}