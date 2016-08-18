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
	/**官网-实现注册
	*       
	*@param string $photo 	头像默认为avatar/head.gif
	*
	*@return status.返回插入的id
	*/
	public function Register($photo="avatar/head.gif")
	{	
		// $nickname=Cache::get("nickname");
		// $mail 	 =Cache::get("mail");
		// $password=Cache::get("password");
		// $password=password_hash($password,PASSWORD_BCRYPT,['cost'=>mt_rand(7,10)]);
		// $login_id=User::Insert([
		// 	"nickname" =>$nickname,
		// 	"mail" =>$mail,
		// 	"password"=>$password,
		// 	"photo" =>$photo,
		// 	"role" =>10,
		// 	"status"=>0
		// 	]);
		// Cache::delete("nickname");
		// Cache::delete("mail");
		// Cache::delete("password");
		// response(['status'=>$login_id]);		
	}
	
	/**官网-更换头像
	*@param string $form_filename 头像上传组名
	*@param string $path 头像路径默认为avatar/
	*@param int    $id 用户ID
	*
	*@return status.返回头像完整路径
	*/
	public function UploadPhoto($form_filename,$path='avatar/',$id)
	{
		$src = Document::Documents($form_filename, $path);
		User::where('id','=',$id)->Update([
				"photo"=>$src
				]);
		response(["status"=>200,'data'=>$src]);	
	}
	
	/**官网-发送邮件
	*
	*@param string $mail 	邮箱
	*@param string $nickname		昵称
	*@param string $password     密码  
	*@param string $Vcheckdata 		验证码
	*
	*@return status.状态/错误 
	*/
	public function sendEmail($mail,$nickname,$password,$Vcheckdata=NULL)
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
				response(['status'=> 408,'errmsg' =>'邮箱已存在']);
			}else{
				if($this->CheckVerify($Vcheckdata)){				
					// Cache::set([
					// "nickname"=>$nickname,
					// "mail"=>$mail,
					// "password"=>$password					
					// ]);
					Mail::to($mail)->title("WangYuanStudio")->content("欢迎注册网园官网用户");
					response(['status'=>200,'data'=>"已发送邮件"]);	
					}	 		
			}
		}else{
			response(['status'=>409,'errmsg'=>'邮箱格式错误']);
		}		 
	}

	/**官网-获取验证码图片
	*
	*@return status.返回验证码图片
	*/

	public function Vphoto()
	{		
		$verify=new Vcode();	
		$verify->show();
		$Vdata=$verify->getData();		
		Session::set("Vda",$Vdata);		
	}

	/**官网-获取验证
	*
	*@param string $Vcheckdata  输入的验证码
	*
	*@return status.返回状态/错误
	*/
	public function CheckVerify($Vcheckdata=NULL)
	{	
		$verify=Session::get("Vda.text");		
		if(is_null($Vcheckdata))
		{
			response(["status"=>404,'errmsg'=>"验证码为空"]);
		}
		else{
			if($verify==$Vcheckdata)
			{		
				response(['stauts'=>200]);		
				return true;				
			}else{
				response(['status'=>405,'errmsg'=>"验证码错误"]);
			}
		}
		return false;
	}

	/**官网-获取验证码类型
	*
	*@return status.返回验证码类型
	*/
	public function GetVtype(){
		response(['stauts'=>Session::get("Vda.type")]);	
	}

	/**官网-限制账号
	*
	*@param int $uid    	用户id
	*
	*@return status.返回状态
	*/
	public function Limituser($uid){
		$user_limit=User::where('id','=',$uid)->Update([
			"status"=>1
			]);	
		if(1==$user_limit){
			response(['status'=>200]);
		}else{
			response(['status'=>406,'errmsg'=>'限制账号操作失败']);
		}
	}
	
	/**官网解除限制账号
	*
	*@param int $uid      用户id
	*
	*@return status.返回状态
	*/
	public function Relieve($uid){
		$user_relieve=User::where('id','=',$uid)->Update([
			"status"=>NULL
			]);	
		if(1==$user_relieve){
			response(['status'=>200]);
		}else{
			response(['status'=>407,'errmsg'=>'解封账号操作失败']);
		}
	}

	public function sd(){
		response([User::select('*')]);
	}
}