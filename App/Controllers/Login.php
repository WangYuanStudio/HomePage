<?php
namespace App\Controllers;

class Login
{
    /**登录接口
   *
   * @param string $mail,$password  参数:邮箱,密码
   * @return status.状态/错误代码，$data成员信息包括id,name,sex,photo,department,habbit,position,blog,phone,introduction

   */
    public function CheckLogin($mail,$password)
    {
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
}