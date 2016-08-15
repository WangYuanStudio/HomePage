<?php
namespace App\Controllers;

class login
{
    /**样本测试
     *
     * @return json name.Zereri
     */
    // public $middle=["kkk"=>"check_login"];
    // public function login()
    // {
    //      $data=["name" => ""];
    //      response($data, "html","login.html");
    // }
    public function checklogin($mail,$password)
    {
         $d=TB('user')->where('mail', '=', $mail)->select();
         //没有找到
            if(sizeof($d)!=0)
            {
                 if(password_verify($password, $d[0]['password']))
                {
                 unset($d[0]['password']); 
                 Session::set("user", $d[0]);
                 $status=["status" => "ok"];
                 response($status, "json");

                }
                else
                {
                    $status=["status" => "密码错误！"];
                    response($status, "json");
                }
            }
            else
            {
                  $status=["status" => "邮箱不存在!"];
                response($status, "json");
            }
    }
    // public function kkk()
    // {
    //    echo "123456";
    //   //  $d=TB('user')->where('mail', '=', $request['mail'])->andWhere('password', '=',$request['password'])->select();
    // }
    // public function index()
    // {
       
    //   //  $d=TB('user')->where('mail', '=', $request['mail'])->andWhere('password', '=',$request['password'])->select();
    //     $data =  ["stdsds" => "dsdsds"];
    //     response($data,"html","index.html");
    // }

}