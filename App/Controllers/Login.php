<?php
namespace App\Controllers;
use  App\Lib\Vcode;
use App\Models\User;
class Login
{

  /*错误代码 && 错误信息
   * 
   * @var array
   */
    public static $error = [
    611 => '密码错误！',
    610 => '该用户被限制登录',
    630 => '该邮箱尚未注册！',
    640 => '验证码过期请刷新!'
    ];
    /**官网——登录接口
   *
   * @param string $mail 邮箱地址
   * @param string $password  密码
    @param string $code 验证码
   * @return status.状态 errmsg.错误信息 data.成员信息包括id,name,sex,photo,department,habbit,position,blog,phone,introduction
   */
    public function CheckLogin($mail,$password,$code)
    {
        //判断验证码的值和验证码的session是否为空
        if($code==Session::get("Vdata.text")&&Session::get("Vdata.text")!=null)
        {
            Session::set("Vdata.text",null); //验证成功后删除验证码
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
                        $status=["status" => "200",'errmsg' => "",'data'=>$d[0]];
                        response($status, "json");

                    }
                    else
                     {
                        $status=["status" => "611",'errmsg' => Login::$error[611],'data'=>''];
                        response($status, "json");
                    }
                }
                else{
                     $status=["status" => "610",'errmsg' => Login::$error[610]，'data'=>''];
                     response($status, "json");
                }
            }
            else
            {
                $status=["status" => "630",'errmsg' =>Login::$error[630]，'data'=>''];
                response($status, "json");
            }
           
        }
        else if(Session::get("Vda")==null)
        {
            $status=["status" => "640",'errmsg' =>Login::$error[640],'data'=>''];
            response($status, "json");  
        }
        else{
            $status=["status" => "302",'msg' => config("common_status")['302'],'data'=>''];
            Session::set("Vda",null); 
            response($status, "json");
        }
    }
  /**官网——获取验证码类行
   *
   *
   * @return data.返回验证码类型
   */
    public function Get_Vtype()
    {
        $data=Session::get("Vdata.type");     
        response($data,'json');
    }
 
}