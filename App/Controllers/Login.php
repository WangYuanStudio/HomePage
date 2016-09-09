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
    public function CheckLogin($mail,$password,$log)
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
          if($d[0]['status']==0)//判断用户是否被限制登录
          {
              if(password_verify($password, $d[0]['password']))//判断密码
              {
                 $get_password=$d[0]['password'];
                 unset($d[0]['password']); 
                 unset($d[0]['overdue']); 
                 unset($d[0]['token']); 
                 $d[0]['ip']=$_SERVER["REMOTE_ADDR"];
                 Session::set("user", $d[0]);//保存session
                 Session::set("errer_num", 0);// 成功后置为0
                 if($log==1) //判断是否保存登录
                 {
                    $time=date("y-m-d H:i:s",time()+60*60*24*7);//设置保存时间的一周
                    if(isset($_COOKIE['token']))//如何存在token值
                    {
                         $statuss= User::where('token', '=',$_COOKIE['token'])->update(['overdue'=>$time]);//更新数据库的过期时间
                         setcookie("token",$_COOKIE['token'], time()+3600+60*60*24*10,'/'); //保存cookie的时间为10天
                     
                    }
                    else{//不存在token值
                         $token=md5(rand(1000000,9999999).time());//设置token值
                         $str= User::where('mail', '=',$d[0]['mail'])->update(['overdue'=>$time,'token'=>$token]);//更新token
                         setcookie("token",$token, time()+3600+60*60*24*10,'/'); //设置token过期为10天
                    }
             
                 }
                 Response::out(200,$d[0]);//返回数据

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

  

    
}