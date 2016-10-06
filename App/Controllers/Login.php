<?php
namespace App\Controllers;

use  App\Lib\Vcode;
use App\Models\User;
use App\Lib\Response;
use App\Lib\Verify;


class Login
{

    public $middle = [
    'GetUserinfo' => 'Check_login'

    ];
  /*错误代码 && 错误信息
   * 
   * @var array
   */
    public static $status = [
    611 => 'password is wrong!',
    610 => 'this user is Restricted landing!',
    630 => 'this mail is not registed!',
    640 => 'Verification code expires!',
    650 => 'password is wrong 3 times or more'
    ];
  /**官网——登录接口
   *
   * @param string $mail 邮箱地址
   * @param string $password  密码
   * @param string $log  判断是否保存用户(1为保存,0为不保存)
   * @return status.状态(609要求验证码) errmsg.错误信息 data.成员信息包括id,nickname,mail,role,ip,photo
   */
    public function CheckLogin($mail,$password,$log=0)
    {
        if(Session::get("errer_num")==null)
        {
          Session::set("errer_num", 0);
        }
      if(Session::get("errer_num")>3)
      {
         if(!Verify::auth()){
            return false;
         }
      }
        $d=User::where('mail', '=', $mail)->select();//判断邮箱是否存在
        if(sizeof($d)!=0)//邮箱存在
        {
          if($d[0]'status']==0)//判断用户是否被限制登录
          {
              if(password_verify($password, $d[0]['password']))//判断密码
              {
                 $get_password=$d[0]['password'];
                 unset($d[0]['password']); 
                 unset($d[0]['overdue']); 
                 unset($d[0]['token']); 
                 $d[0]['ip']=$_SERVER["REMOTE_ADDR"];
                 $data2=[
                    'user'=>$d[0],
                    'error_num'=>0
                  ]
                 $token=sha1($d[0]['nickname'].time().mt_rand(100,999));
                 Cache::set($token,json_encode($data2),60*60*24*7);//保存session
                 Response::out(200,$token);//返回数据

              }
              else
              {
                $p= Session::get("errer_num");
                $p++;
                Session::set("errer_num", $p);
                if($p>3)
                {
                  Response::out(650);
                }
                else
                {
                 Response::out(611);
                }
                
              }
          }
          else
          {
             Response::out(610);
          }
        }
      
        else
        {
          Response::out(630);
        }
           
    }

  /**官网——退出接口
   *
   * @return status.状态 errmsg.错误信息
   */
    public function logout()
    {
      $T= Session::remove("user", null);
      if(isset($_COOKIE['token']))//清空token
      {
          $str= User::where('token', '=',$_COOKIE['token'])->update(['overdue'=>null,'token'=>null]);
           setcookie('token','',time()-360000,"/");
      }
      Response::out(200);
    }
  /**官网——获取登录用户信息
   *
   * @return status.状态 errmsg.错误信息 data.成员信息包括id,nickname,mail,role,ip,photo
   */
    public function GetUserinfo()
    {
          Response::out(200,Session::get("user"));
    }


  public function get()
  {
    Response(__ROOT__);
  }
  public function setdata()
  {
   //    $data=[
   //      'name'=>'dsds',
   //      'ps'=>'123456'

   //    ];
   // $data2=[
   //        'user'=>$data,
   //        'error_num'=>0
   //        ];

   //    Response(json_encode($data2));
   // Cache::set('name','dsdsds');
    //Response(Cache::get('name'));
  }
  

    
}