<?php
/*
*User: huizhe
*Date: 2016/8/12
*Time: 13:18
*/

namespace App\Controllers;

use App\Models\User;
use App\Lib\Document;
use App\Lib\Mail;
use App\Lib\Vcode;

 class Enroll
 {
	/**实现注册
	*       
	*@param string photo 		头像  默认为 avatar/head.gif
	*
	*return status.返回插入的id
	*/
	public function Register($photo="avatar/head.gif")
	{	
		$nickname=cache::get("nickname");
		$mail 	 =cache::get("mail");
		$password=cache::get("password");
		$password=password_hash($password,PASSWORD_BCRYPT,['cost'=>mt_rand(7,10)]);
		$login_id=User::Insert([
			"nickname" =>$nickname,
			"mail" =>$mail,
			"password"=>$password,
			"photo" =>$photo,
			"role" =>4
			]);
		cache::flush();
		response(['status'=>$login_id]);		
	}
	
	/**更换头像
	*@param int id 用户ID
	*@param string form_filename 头像上传组名
	*@param string path 头像路径，默认为avatar/
	*
	*return status.返回头像完整路径
	*/
	public function UploadPhoto($form_filename,$path='avatar/',$id)
	{
		$src = Document::Documents($form_filename, $path);
		User::where('id','=',$id)->Update([
				"photo"=>$src
				]);
		response(["status"=>$src]);	
	}
	
	/**发送邮件
	*
	*@param string mail 	邮箱
	*@param string title 	标题
	*@param string content   发送内容
	*@param string nickname		昵称
	*@param string password     密码  
	*
	*return status.状态/错误 
	*/
	public function sendEmail($mail,$title,$content,$nickname,$password)
	{
		$checkdata=0;
		if(preg_match("/([\w\-]+\@[\w\-]+\.[\w\-]+)/",$mail))
		{
			$data=User::where('mail','=',$mail)->select('mail');
			foreach($data as $value){
				if(in_array($mail, $value)){
					$checkdata=1;
				}
			}
			if(1==$checkdata)
			{
				response(['status'=> -1,'msg' =>'邮箱已存在']);
			}else{				
				cache::set([
					"nickname"=>$nickname,
					"mail"=>$mail,
					"password"=>$password
					Mail::to($mail)->title($title)->content($content);
					response(['status'=>1,'msg'=>"已发送邮件"]);
					]);
		 		response("status");
			}
		}else{
			response(['status'=>-2,'msg'=>'邮箱格式错误']);
		}		 
	}

	/**获取验证码图片
	*
	*return status.
	*/

	public function Vphoto()
	{		
		$verify=new Vcode();	
		$verify->show();
		$Vdata=$verify->getData();
		session::set("Vda",$Vdata["text"]);		
	}

	/**获取验证
	*
	*@param string Vcheckdata  输入的验证码
	*
	*return status.返回状态/错误
	*/
	public function CheckVerify($Vcheckdata)
	{
		$verify=session::get("Vda");
		if($verify==$Vcheckdata)
		{
			response(['status'=>1,'msg'=>"验证码正确"]);
		}else{
			response(['status'=>-1,'msg'=>"验证码错误"]);
		}
		session::remove("Vda");
	}
}