<?php
namespace App\Controllers;
use  App\Lib\Vcode;
use App\Models\User;
use App\Lib\Response;
class Login
{

  /*错误代码 && 错误信息
   * 
   * @var array
   */
  public static $num=0;
    public static $status = [
    611 => 'password is wrong！',
    610 => 'this user is Restricted landing!',
    630 => 'this mail isn’t registed！',
    640 => 'Verification code expires!'
    ];
    /**官网——登录接口
   *
   * @param string $mail 邮箱地址
   * @param string $password  密码
    @param string $code 验证码
   * @return status.状态 errmsg.错误信息 data.成员信息包括id,name,sex,photo,department,habbit,position,blog,phone,introduction
   */
  //  $mail,$password,$code
    public function CheckLogin()
    {
      if(Session::get("errer_num")==null)
      {
         Session::set("errer_num", 0);
      }
        //判断验证码的值和验证码的session是否为空

            $d=User::where('mail', '=', $mail)->select();//判断邮箱是否存在
            if(sizeof($d)!=0)//邮箱存在
            {
                if($d[0]['status']==1)//判断用户是否被限制登录
                {
                    if(password_verify($password, $d[0]['password']))//判断密码
                    {
                        unset($d[0]['password']); 
                         $d[0]['ip']=$_SERVER["REMOTE_ADDR"];
                        Session::set("user", $d[0]);//保存session
                     //   $status=["status" => "200",'errmsg' => "",'data'=>$d[0]];
                        Session::set("errer_num", 0);// 成功后置为0
                        Response::out(200,$d[0]);
                     //   response($status, "json");


                    }
                    else
                     {
                     //   $status=["status" => "611",'errmsg' => Login::$error[611],'data'=>''];
                    //    response($status, "json");
                        $p= Session::get("errer_num");
                        $p++;
                        Session::set("errer_num", $p);
                        Response::out(611,Session::get("errer_num"));
                    }
                }
                else{
                  //   $status=["status" => "610",'errmsg' => Login::$error[610],'data'=>''];
                    // response($status, "json");
                      Response::out(610);
                }
            }
            else
            {
             //   $status=["status" => "630",'errmsg' =>Login::$error[630],'data'=>''];
               // response($status, "json");
                 Response::out(630);
            }
           
    }

  
 
}