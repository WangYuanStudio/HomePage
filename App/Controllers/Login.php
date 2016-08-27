<?php
namespace App\Controllers;
use  App\Lib\Vcode;
use App\Models\User;
use App\Lib\Response;
class Login
{
    public $middle = [
    'index' => 'Check_login'

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
   * @return status.状态(609要求验证码) errmsg.错误信息 data.成员信息包括id,name,sex,photo,department,habbit,position,blog,phone,introduction
   */
    public function CheckLogin( $mail,$password)
    {
        if(Session::get("errer_num")==null)
        {
          Session::set("errer_num", 0);
        }
        $d=User::where('mail', '=', $mail)->select();//判断邮箱是否存在
        if(sizeof($d)!=0)//邮箱存在
        {
          if($d[0]['status']==0)//判断用户是否被限制登录
          {
              if(password_verify($password, $d[0]['password']))//判断密码
              {
                 unset($d[0]['password']); 
                 $d[0]['ip']=$_SERVER["REMOTE_ADDR"];
                 Session::set("user", $d[0]);//保存session
                 Session::set("errer_num", 0);// 成功后置为0
                 Response::out(200,$d[0]);
              }
              else
              {
                $p= Session::get("errer_num");
                $p++;
                Session::set("errer_num", $p);
                if($p>=3)
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
      Response::out(200);
    }
    
}