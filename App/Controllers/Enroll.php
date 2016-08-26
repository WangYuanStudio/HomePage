<?php
/*
*User: huizhe
*Date: 2016/8/26
*Time: 14:22
*/

namespace App\Controllers;

use App\Models\User;
use App\Lib\Document;
use App\Lib\Mail;
use App\Lib\Vcode;
use App\Lib\Response;

 class Enroll
 {
 // 	public $middle = [
	// 	'UploadPhoto' => 'Check_login',
	// 	'Limituser' => 'Check_login',
	// 	'Relieve' => 'Check_login',
	// 	'Updatepsw' => 'Check_login',
	// 	'Updateuser' => 'Check_login',
	//  'Sendverify' => 'Check_login'
	// 	// 对所有方法判断登录
	// ];

 	public static $status=[
		405 => 'Verification code is empty.',
		406 => 'Restricted account operation failed,Mysql operation failure.',
		407 => 'Unlock account operation fails,Mysql operation failure.',
		408 => 'Your email has been in existence.',
		409 => 'Email format error.',
		410 => 'Replace the face failure.',
		411 => 'Token error.',
		412 => 'Email does not exist.',
		413 => 'Time out.',
		414 => 'Do not match the password input.',
		415 => 'Change the password failure.',
	//	416 => 'Password mistake.',
		417 => 'Modify personal information failure.'
	];

	/**官网-实现注册
	*       
	*@param string $photo 	头像默认为avatar/head.gif
	*
	*@return login_id.返回插入的id
	*/
	public function Register($photo="avatar/head.gif")
	{	
		$token = stripslashes(trim($_POST['verify']));
		if(time()<=Cache::get($token."register_time")){
			if($token==Cache::get($token."register_token")){									
				$password=Cache::get($token."password");
				$password=password_hash($password,PASSWORD_BCRYPT,['cost'=>mt_rand(7,10)]);
				$login_id=User::Insert([
				"nickname" =>Cache::get($token."nickname"),
				"mail" =>Cache::get($token."mail"),
				"password"=>$password,
				"photo" =>$photo,
				"role" =>10,
				"status"=>0
				]);
				Cache::delete([$token."nickname",$token."mail",$token."password",$token."register_time",$token."register_token"]);
				Response::out(200,['login_id'=>$login_id]);					
			}else{
				Response::out(411);
			}
		}else{
			Response::out(413);
		}			
	}
	
	/**官网-更换头像
	*@param string $form_filename 头像上传组名
	*@param string $path 头像路径默认为avatar/
	*@param int    $id 用户ID
	*
	*@return status.状态码
	*/
	public function UploadPhoto($form_filename,$path='avatar/',$id)
	{
		$src = Document::Upload($form_filename, $path);
		$check=User::where('id','=',$id)->Update([
				"photo"=>$src
				]);
		if(1==$check){
			Response::out(200);
		}else{
			Response::out(410);
		}	
	}
	
	/**官网-发送邮件
	*
	*@param string $mail 	邮箱
	*@param string $nickname		昵称
	*@param string $password     密码  
	*
	*@return status.状态码 
	*/
	public function sendEmail($mail,$nickname,$password)
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
				Response::out(408);
			}else{					
					$token=md5(rand(10000,99999).time());			
					Cache::set([
					$token."nickname"=>$nickname,
					$token."mail"=>$mail,
					$token."password"=>$password,				
					$token."register_token" =>$token,
					$token."register_time"	=>time()+30*60
					]);															
					$emailbody = "亲爱的".$nickname."：<br/>感谢您在我站注册了新帐号。<br/>请点击链接激活您的帐号。<br/>
					（注：链接有效时间为30分钟，超时链接失效请重新进行申请操作）<br/>
    <a href='http://127.0.0.1:8080/Enroll/Register?verify=".$token."' target= 
'_blank'>/http://127.0.0.1:8080/Enroll/Register?verify=".$token."</a><br/> 
    如果以上链接无法点击，请将它复制到你的浏览器地址栏中进入访问"; 
					Mail::to($mail)->title("WangYuanStudio")->content($emailbody);
					//Session::remove("code");
					Response::out(200);								
			}
		}else{
			Response::out(409);
		}		 
	}


	/**官网-限制账号
	*
	*@param int $uid    	用户id
	*
	*@return status.状态码
	*/
	public function Limituser($uid){
		$user_limit=User::where('id','=',$uid)->Update([
			"status"=> 1
			]);			
		if(1==$user_limit){
			Response::out(200);
		}else{
			Response::out(406);
		}
	}
	
	/**官网解除限制账号
	*
	*@param int $uid      用户id
	*
	*@return status.状态码
	*/
	public function Relieve($uid){
		$user_relieve=User::where('id','=',$uid)->Update([
			"status"=>0
			]);	
		if(1==$user_relieve){
			Response::out(200);
		}else{
			Response::out(407);
		}
	}

	/**官网-找回密码之发送邮箱
	*
	*@param string $mail 	邮箱
	*
	*@return status.状态码
	*/
	public function Searchpsw($mail){
		if(preg_match("/([\w\-]+\@[\w\-]+\.[\w\-]+)/",$mail))
		{
			$checkdata=0;
			$user_nickname='user';
			$data=User::where('mail','=',$mail)->select();
			foreach($data as $key => $value){
				if(in_array($mail,$value)){
					$checkdata=1;
					$checkvalue=$value;
					if(1==$checkdata){
						foreach($checkvalue as $key =>$value){
							if($key=='nickname')
								$user_nickname=$value;
						}
					}
				}
			}
			if(1==$checkdata){
				$token=md5(rand(1000000,9999999).time());
				Cache::set([
					$token."search_token"=>$token,
					$token."search_time" =>time()+30*60,
					$token."search_mail" =>$mail
				]);		
				$emailbody = "亲爱的".$user_nickname."，您好"."：<br/>请您点击以下链接进行找回密码，即可生效！<br/> 
			（注：链接有效时间为30分钟，超时链接失效请重新进行申请操作）<br/>     
			<a href='http://127.0.0.1:8080/Enroll/Supdatepsw?verify=".$token."' target= 
'_blank'>/http://127.0.0.1:8080/Enroll/Supdatepsw?verify=".$token."</a><br/> 			
     如果以上链接无法点击，请将它复制到你的浏览器地址栏中进入访问"; 
				Mail::to($mail)->title("WangYuanStudio")->content($emailbody);
				Response::out(200);
			}else{
			Response::out(412);
			}
		}else{
			Response::out(409);
		}
	}

	/**官网-找回密码之修改密码
	*
	*@param string $password    	密码1
	*@param string $password2		密码2
	*
	*@return status.状态码
	*/
	public function Supdatepsw($password,$password2)
	{
		$token = stripslashes(trim($_POST['verify']));
		if(time()<=Cache::get($token."search_time")){
			if($token==Cache::get($token."search_token"))
			{
				if($password==$password2)
				{
					$update_psw=User::where('mail','=',Cache::get($token."search_mail"))->Update([
						"password" =>$password
						]);
					if(1==$update_psw)
					{
						Response::out(200);
						Cache::delete([$token."search_time",$token."search_token",$token."search_mail"]);
					}else{
						Response::out(415);
					}
				}else{
					Response::out(414);
				}
			}else
			{
				Response::out(411);
			}
		}else{
			Response::out(413);
		}
	}

	/**官网-修改密码
	*
	*@param int $uid 		用户id
	*@param string $password1		新密码1
	*@param string $password2		新密码2
	*@param string $re_verify       验证码
	*
	*@return status.状态码
	*/
	public function Updatepsw($uid,$password1,$password2,$re_verify)
	{
		if($this->Updateverify($re_verify))
		{
			if($password1==$password2){
				$update_psw=User::where('id','=',$uid)->Update([
					"password" =>$password1
					]);
				if(1==$update_psw){
					Cache::delete("send_verify");
					Response::out(200);
				}else{
					Response::out(415);
				}
			}else{
				Response::out(414);
			}		
		}else{
			Response::out(302);
		}
	}

	/**官网-修改个人信息
	*
	*@param int $uid       用户id
	*@param string $mail 	邮箱
	*@param string $nickname		昵称
	*
	*@return status.状态码
	*/

	public function Updateuser($uid,$mail,$nickname){
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
				Response::out(408);
			}else{
				$update_user=User::where('id','=',$uid)->Update([
					"mail" =>$mail,
					"nickname" => $nickname
					]);	
				if(1==$update_user){			
					Response::out(200);
				}else{
					Response::out(417);
				}							 		
			}
		}else{
			Response::out(409);
		}
	}

	/**官网-修改密码之发送邮箱
	*
	*@param string $nickname     用户昵称
	*@param string $mail         用户邮箱
	*
	*@return status.状态码
	*/
	public function Sendverify($nickname,$mail)
	{
		$verify=rand(100000,999999);
		$emailbody = "亲爱的".$nickname."，您好"."：<br/>验证码为".$verify;   
		Cache::set($verify."send_verify",$verify);  					   
		Mail::to($mail)->title("WangYuanStudio")->content($emailbody);	
		Response::out(200);	
	}

	//修改密码之验证码验证
	public function Updateverify($verify=NULL)
	{
		if($verify==Cache::get($verify."send_verify"))
		{
			return true;
		}else{
			return false;
		}
	}
	
}