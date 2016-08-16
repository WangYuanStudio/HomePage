<?php
namespace App\Controllers;
use  App\Lib\Vcode;
class Login
{
    /**登录接口
   *
   * @param string $mail,$password  参数:邮箱,密码
   * @return status.状态/错误代码，$data成员信息包括id,name,sex,photo,department,habbit,position,blog,phone,introduction

   */
    public function CheckLogin($mail,$password,$code)
    {
        if($code==Session::get("Vda")&&Session::get("Vda")!=null)
        {
            Session::set("Vda",null); 
            $d=TB('user')->where('mail', '=', $mail)->select();
            if(sizeof($d)!=0)
            {
                if(password_verify($password, $d[0]['password']))
                {
                 unset($d[0]['password']); 
                 Session::set("user", $d[0]);
                 $status=["status" => "1",'msg' => "登录成功！"];

                 response($status, "json");

                }
                else
                {
                    $status=["status" => "0",'msg' => "密码错误！"];
                    response($status, "json");
                }
            }
            else
            {
                $status=["status" => "0",'msg' => "邮箱不存在！"];
                response($status, "json");
            }
           
        }
        else if(Session::get("Vda")==null)
        {
            $status=["status" => "0",'msg' => "验证码过期请刷新"];
            response($status, "json");  
        }
        else{
            $status=["status" => "0",'msg' => "验证码错误！"];
            Session::set("Vda",null); 
            response($status, "json");
        }
    }

    public function Get_Vtype()
    {
        $data=Session::get("Vtype");     
        response($data,'json');
    }
 
}