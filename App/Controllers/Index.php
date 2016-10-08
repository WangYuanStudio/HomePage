<?php
namespace App\Controllers;
use App\Models\Member;
use App\Models\Message;
use App\Models\Article;
use App\Lib\Response;
use App\Lib\Document;
use App\Lib\Html;

class Index
{
  const En_Photo_UPLOAD='avatar/';
  const En_Photo_REGISTER='avatar/15.png';

  const En_Photo_FILE='file';
  public $middle = [
   
    // 'Send_message' => ['Check_login','Check_Operation_Count'],
    // 'check_mes' => ['Check_login','Check_Managermes'],
    // 'del_mes' => ['Check_login','Check_Managermes'] ,
    // 'edt_message' =>['Check_login','Check_Managermes'],
    // 'edt_article' => ['Check_login','Check_Managerarticle'],
    // 'del_article' => ['Check_login','Check_Managerarticle'] ,
    // 'publish_article' => ['Check_login','Check_Managerarticle','Check_Operation_Count'],
    // 'del_member' => ['Check_login','Check_ManagerMember'] ,
    // 'edt_member' =>['Check_login','Check_ManagerMember'],
     // 'del_allmes'=>['Check_login','Check_Managermes']
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
   * @group 官网成员管理
   * 
   * @param enum $department 部门名称(design,frontend,backend,secretary,all,front_design)
   * @param int $num  每页的数量
   * @param int $page 页数
   * @return status:状态 errmsg:错误信息 data.id:用户id data.name:成员名字 data.photo:成员头像 data.department:成员部门 data.position:成员职位 data.introduction:成员介绍 data.mail:成员邮箱 data.year:成员年级
   * @example 200 {"status": 200, "data": [{"id": "14", "name": "黎明", "photo": "avatar/20160917135848_334.jpg", "department": "backend", "position": "成员", "phone": "12345678910", "introduction": "以前我没得选，现在我想打人", "year": "14", "mail": "798456@qq.com"}, {"id": "15", "name": "林紫钊", "photo": "avatar/20160917184740_442.jpg", "department": "backend", "position": "成员", "phone": "12345678910", "introduction": "以前我没得选，现在我想打人", "year": "15", "mail": "798456@qq.com"} ] }
   * @example 601 {"status": 601, "errmsg": "no member information"}
   */
    public function ShowMember($department,$num,$page = 1)
    {
        if($department=='all')
        {
          $data=Member::limit(($page-1)*$num,$num)->select();
        }
        else if($department=='front_design')
        {
          $department1="frontend";
          $department2="design";
           $data=Member::where("department","=",$department1)->orWhere("department","=",$department2)->limit(($page-1)*$num,$num)->select();
        }
        else
        {
          $data=Member::where("department","=",$department)->limit(($page-1)*$num,$num)->select();
        }
        if(sizeof($data)!=0)
        {
              Response::out(200,$data);
        }
        else
        {
            Response::out(601);
        }
    }
     
  /**官网—获取游客留言信息
   *
   * @group 官网留言
   *@param int $auto 留言状态(1,为未审核,2为审核,3为所有)
    *@param int $num 每一页的数量
   *@param int $page 页数
   *@example 200 {"status": 200, "data": [{"id": "12", "uid": null, "content": "sadsaf", "time": "2016-09-17 00:30:14", "auth": "0", "nickname": null }, {"id": "11", "uid": null, "content": "dadfasdf", "time": "2016-09-17 00:29:19", "auth": "0", "nickname": null } ] }
   *@example 602 {"status": 602, "errmsg": "no message!"}
   * @return status:状态 errmsg:错误信息 data.nickname:用户名 date.message:用户留言信息 data.time:留言时间 data.auto:审核状态(0未审核,1已审核)
   */
    public function GetMessage($auto,$num,$page=1)
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

  /**官网—游客发表留言
    * @group 官网留言
   ** @param string $message 留言信息(字符串长度<600)
    * @return status:状态 errmsg:错误信息
    * @example 200 {"status": 200, "data": null }
    * @example 603 {"status": 200, "errmsg": "fail to send message" }
   */
    public function Send_message($message)
    {
      $time= date('y-m-d H:i:s',time());
      $str= Message::insert(['uid'=>Session::get("user.id"),'content' => Html::removeSpecialChars($message), 'time' => $time, 'auth' => 0]);
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
    * @group 官网留言
   **@param int $id 留言id
   * @return status:状态 errmsg:错误信息
   * @example 200 {"status": 200, "data": null }
  * @example 604 {"status": 604, "errmsg": "fail to send delete" }
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
   * @group 官网留言
   * @param int $id 留言id
   * @return status:状态 errmsg:错误信息
    * @example 200 {"status": 200, "data": null }
    * @example 605 {"status": 605, "errmsg": "fail to send delete" }
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
    *@group 网园动态
   ** @param string $title 标题(字符串长度<255)
    ** @param string $content 类容
    * @return status:状态 errmsg:错误信息
    * @example 200 {"status": 200, "data": null }
    * @example 606 {"status": 606, "errmsg": "fail to send a dynamic" }
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
    *@group 网园动态
    * @param string $id 动态的id
    * @return status:状态 errmsg:错误信息
    * @example 200 {"status": 200, "data": null }
    * @example 605 {"status": 605, "errmsg": "fail to send delete" }
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
  *@group 网园动态
    *@param int $num 每一页的数量
   *@param int $page 页数
   * @return status:状态 errmsg:错误信息 data.title:标题 data.content:内容 data.time:时间 data.id:动态id
   *@example 200 {"status": 200, "data": [{"id": "8", "title": "WOAINI", "content": "FSDAFDSAFSD", "time": "2016-10-07 19:55:15", "img": null }, {"id": "7", "title": "第二", "content": "fdsfdsfdsf'd's得得45645645645645646456", "time": "2016-09-08 16:32:33", "img": null } ] }
   *@example 607 {"status": 607, "errmsg":"no dynamic"}
   */
    public function get_article($num,$page=1)
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
    *@group 网园动态
   ** @param int $id 动态的id
   ** @param string $title 动态的标题(字符串长度<255)
   ** @param string $content 动态的类容
    * @return status:状态 errmsg:错误信息
   * @example 200 {"status": 200, "data": null }
    * @example 608 {"status": 608, "errmsg": "fail to edit" }
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
/**官网—修改成员信息
    *
   *@group  官网成员管理
  ** @param string $id 成员id
  ** @param string $name 成员姓名(长度<10)
  ** @param string $position 职位
  ** @param string $phone 电话(长度<=11)
  ** @param int $year 年份(14,15)
  ** @param string $introduction 介绍(长度<255)
  ** @param string $mail 邮箱 
  ** @param enum $department 部门(design,frontend,backend,secretary)
  * @return status:状态 errmsg:错误信息
   * @example 200 {"status": 200, "data": null }
  * @example 608 {"status": 608, "errmsg": "fail to edit" }
   */
    public function edt_member($id,$name,$position,$phone,$introduction,$mail,$department,$year)
    {
       $statuss= Member::where('id', '=',$id)->update(
        ['name' => $name, 
        'position'=>$position,
        'phone'=>$phone,
        'introduction'=>$introduction,
        'mail'=>$mail,
        'department'=>$department,
        'year'=>$year
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
    * @group 官网成员管理
    * @param string $id 成员的id
    * @return status:状态 errmsg:错误信息
    * @example 200 {"status": 200, "data": null }
    * @example 608 {"status": 605, "errmsg": "fail to delete" }
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
    * @group 官网留言
   ** @param string $id 留言的id
   ** @param string $content 留言的内容
   * @return status:状态 errmsg:错误信息
   * @example 200 {"status": 200, "data": null }
  * @example 608 {"status": 608, "errmsg": "fail to edit" }
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
  * @group 官网成员管理

  ** @param string $name 成员姓名(长度<10)
  ** @param string $position 职位
  ** @param string $department 部门(design,frontend,backend,secretary)
  ** @param string $phone 电话(长度<=11)
  ** @param string $introduction 介绍(长度<255)
  ** @param int $year 年份(14,15)
  ** @param string $mail 邮箱 
   * @return status:状态 errmsg:错误信息
   * @example 200 {"status": 200, "data": null }
  * @example 609 {"status": 609, "errmsg": "fail to insert" }
   */
    public function add_member($name,$position,$phone,$introduction,$mail,$department,$year)
    {
      if(isset($_FILES[self::En_Photo_FILE]['name']))
      {
       $name2=$_FILES[self::En_Photo_FILE]['name'];
       $allowtype=array('png','gif','bmp','jpeg','jpg');
       $aryStr=explode(".",$name2);
       $filetype=strtolower($aryStr[count($aryStr)-1]);
    if(in_array(strtolower($filetype),$allowtype)){
      $src = Document::Upload(self::En_Photo_FILE, self::En_Photo_UPLOAD);
      }
    }
    else
    {
      $src =self::En_Photo_REGISTER;
    }
      $str= Member::insert(['photo'=>$src,'name'=>$name,'position'=>$position,'phone'=>$phone,'introduction'=>$introduction,'department'=>$department,'mail'=>$mail,'year'=>$year]);
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
   * @param file $file 上传文件控件
   * @param string $id 成员id
   * @return status.状态 errmsg.错误信息
   */
    public function edt_member_pho($id)
    {
       $name=$_FILES[self::En_Photo_FILE]['name'];
       $allowtype=array('png','gif','bmp','jpeg','jpg');
       $aryStr=explode(".",$name);
       $filetype=strtolower($aryStr[count($aryStr)-1]);
       $da=Member::where('id', '=',$id)->select('photo');
    if(in_array(strtolower($filetype),$allowtype)){
      $src = Document::Upload(self::En_Photo_FILE, self::En_Photo_UPLOAD);
      $statuss= Member::where('id', '=',$id)->update(
        [
        'photo'=>$src
        ]);
      }
        if($statuss==1)
        {
          $result = @unlink (__ROOT__.'/public/'.$da[0]['photo']); 
          Response::out(200,$src);
        }
        else
        {
           Response::out(608);
        }
    }
  /**官网—一键审核留言
   * @group 官网留言
   *
   * @param string $list  一个包含要审核留言的id数组
   * @return status:状态 errmsg:错误信息
   */
    public function del_allmes($list)
    {
      foreach ($list as $key => $id)
      {
         $status=  Message::where('id','=',$id)->update(['auth' => '1']);
      }
      Response::out(200);
    }
  /**官网—获取总页数(分页时使用)
   * @group 官网
   * @param enum $title 在(member,message,article)中选择一个值
   * @param int $num  每页的条数
   * @param int $auto  留言状态(1,为未审核留言,2为审核,3为所有)只当第一个参数title选择为message时需设置，否则不设置。
   * @param string $department  部门名称(frontend,backend,secretary,all)只当第一个参数title选择为member时需设置，否则不设置。
   * @return status:状态 errmsg:错误信息 data:数据总页数
   * @example 200 {"status": 200, "data": 2 }
   */
    public function total_message($title,$num,$auto=1,$department='all')
    {
      if($title=='message')
      {
       $auto--;
       if($auto==0||$auto==1)
       {
      
        Response::out(200,ceil(count(Message::leftJoin('user', 'message.uid', '=', 'user.id')->where("auth","=",$auto)->select('message.uid'))/$num));
       }
       else if($auto==2)
       {
          Response::out(200,ceil(count(Message::leftJoin('user', 'message.uid', '=', 'user.id')->orderBy('time desc')->select('message.uid'))/$num));
       }
       else
       {
         Response::out(602);
         return false;
       }
     }
     else if($title=='member')
     {
        if($department=='all')
        {
            Response::out(200,ceil(count(Member::select('id'))/$num));
        }
        else
        {
           Response::out(200,ceil(count(Member::where("department","=",$department)->select('id'))/$num));
        }
     }
     else 
     {
         Response::out(200,ceil(count(article::select('id'))/$num));
     }

    }
  /**官网—获取搜索关键字的总页数
   * @group 官网
   * @param string $key  关键字
   * @param enum $title 在(member,message,article)中选择一个值
   * @param int $num  每页的条数
   * @return status:状态 errmsg:错误信息 data:数据总页数
   * @example 200 {"status": 200, "data": 2 }
   */
    public function search_page($key,$title,$num)
    {
      if($title=='member')
      {
        $d=ceil(count(TB('')->raw('select id from member where name like "%'.$key.'%"',[1]))/$num);
        Response::out(200,$d);
      }  
      if($title=='message')
      {
         $d=ceil(count(TB('')->raw('select id from message where content like "%'.$key.'%"',[1]))/$num);
         Response::out(200,$d);
      }
      if($title=='article')
      {
         $d=ceil(count(TB('')->raw('select id from article where title like "%'.$key.'%" or content like "%'.$key.'%"',[1]))/$num);
         Response::out(200,$d);
      }
    }

  /**官网—获取搜索内容
  * @group 官网
   * @param string $title 在(member,message,article)中选择一个值
   *@param string $key 关键字
   *@param int $auto 留言状态(1,为未审核,2为审核,3为所有)只当第一个参数title选择为message时需设置，否则不设置。
   *@param int $page 页数
   *@param int $num 每页的条数
   * @return status:状态 errmsg:错误信息 data.id:(member,message,article)的id，data.name:成员名字 data.photo:成员头像 data.department:成员部门 data.position:成员职位 data.introduction:成员介绍 data.mail:成员邮箱 data.year:成员年级 data.nickname:用户名 date.message:用户留言信息 data.time:留言时间 data.auto:审核状态(0未审核,1已审核) data.title:标题 data.content:内容 data.time:时间
   *@example 200 {"status": 200, "data": [{"id": "6", "name": "我是栗子", "photo": "avatar/20160919033438_668.jpg", "department": "frontend", "position": "成员", "phone": "13727583998", "introduction": "以前我没得选，现在我想打人", "year": "16", "mail": "789456789@qq.com"} ]}
   *@example 201 {"status": 200, "data": [{"id": "5", "uid": "9", "content": "以前我没的选，现dsdsdsdsdsds", "time": "2016-09-08 16:35:29", "auth": "0", "nickname": "intern62100"}, {"id": "6", "uid": "9", "content": "以前我没的选，现dsdsdsdsdsds", "time": "2016-09-08 16:35:29", "auth": "0", "nickname": "intern62100"}, {"id": "7", "uid": "9", "content": "以前我没的选，现dsdsdsdsdsds", "time": "2016-09-08 16:35:29", "auth": "0", "nickname": "intern62100"} ] }
   */
    public function search_data($title,$key,$page=1,$num,$auto=2)
    {
      if($title=='article')
      {
       $d=TB('')->raw ("select *  from article  where title  like '%".$key."%' or content like '%".$key."%'  order by time desc limit ".($page-1)*$num.",".$num."",[1]);
       Response::out(200,$d);
      }
      if($title=='message')
      {
        $auto--;
        if($auto==2)
        {
            $d=TB('')->raw ("select message.*,user.nickname from message left join user on message.uid=user.id  where message.content  like '%".$key."%' order by message.time desc limit ".($page-1)*$num.",".$num."",[1]);
        }
        else
        {
        $d=TB('')->raw ("select message.*,user.nickname from message left join user on message.uid=user.id  where message.content  like '%".$key."%' and message.auth = ".$auto." order by message.time desc limit ".($page-1)*$num.",".$num."",[1]);
        }
        Response::out(200,$d);
      }
      if($title=='member')
      {
       $d=TB('')->raw ("select *  from member  where name  like '%".$key."%'  limit ".($page-1)*$num.",".$num."",[1]);
       Response::out(200,$d);
      }
    }


  /**官网—获取未审核留言的条数(消息提示)
   * @return status.状态 errmsg.错误信息 data.数据条数
   */
    public function getnocheckmessage()
    {
      $d=Message::leftJoin('user', 'message.uid', '=', 'user.id')->where("auth","=",0)->orderBy('time desc')->select('message.*,user.nickname');
      Response::out(200,sizeof($d));
    }

    

}