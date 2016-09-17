<?php
/*
*User: huizhe
*Date: 2016/9/17
*Time: 15:02
*/

namespace App\Controllers;

use App\Models\User;
use App\Lib\Document;
use App\Lib\Mail;
use App\Lib\Vcode;
use App\Lib\Response;
use App\Lib\Html;
use App\Lib\Verify;
use App\Controllers\Login;

 class Enroll
 {
 	const En_Photo_UPLOAD='avatar/';
 	const En_Photo_REGISTER='avatar/head.gif';
 	const En_Photo_FILE='file';
 	
 	public $middle = [
	'UploadPhoto' => 'Check_login',
	'Limituser' => ['Check_login','Check_ManagerMember'],
	'Relieve' => ['Check_login','Check_ManagerMember'],
	'Updatepsw' => 'Check_login',
	'Updateuser' => 'Check_login',
	'Sendverify' => ['Check_login','Check_Operation_Count'],
	'sendEmail'  =>'Check_Operation_Count',	
	'Searchpsw'  =>'Check_Operation_Count'
		// 对所有方法判断登录
	];

 	public static $status=[
		405 => 'Verification code is empty.',
		406 => 'Restricted account operation failed.',
		407 => 'Unlock account operation fails.',
		408 => 'Your email has been in existence.',
		409 => 'Email format error.',
		410 => 'Replace the face failure.',
		411 => 'Token error.',
		412 => 'Email does not exist.',
		413 => 'Time out.',
		414 => 'Do not match the password input.',
		415 => 'Change the password failure.',
	//	416 => 'Password mistake.',
		417 => 'Modify personal information failure.',
		418 => 'Password length less than 6',
		419 => 'Email has been sent, please wait a moment',
		421 => 'Only allowed to upload pictures.',
		423 => 'Please send registered mail.',
		424 => 'Please send back the password email.'
	];

	/**官网-实现注册
	*         
	*
	*@return login_id.返回插入的id
	*/
	public function Register()
	{
	
		$token=$_GET['token'];
		//判断是否已发送邮箱	
		if(!Cache::has($token)){
			Response::out(423);
			return false;
		}
		//获取缓存数据
		$array=json_decode(Cache::get($token));		
		foreach ($array as $key => $value) {
			if('time'==$key)
				$time=$value;
			if('token'==$key)
				$token_verify=$value;
			if('password'==$key)
				$password=$value;
			if('mail'==$key)
				$mail=$value;
			if('nickname'==$key)
				$nickname=$value;
		}
		//判断时间
		if(time()>$time){
			Response::out(413);
			return false;
		}
		//判断token			
		if($token!=$token_verify){									
			Response::out(411);
			return false;
		}		
		$password=Html::removeSpecialChars($password);
		$password=password_hash($password,PASSWORD_BCRYPT,['cost'=>mt_rand(7,10)]);
		$login_id=User::Insert([
		"nickname" =>$nickname,
		"mail" =>$mail,
		"password"=>$password,
		"photo" =>self::En_Photo_REGISTER,
		"role" =>10,
		"status"=>0
		]);		
		Cache::delete([$token,$mail]);
		Response::out(200,['login_id'=>$login_id]);	
	}
	
	/**官网-用户更换头像
	*@param string $file 1 上传控件
	*
	*@return status.状态码 data.照片路径
	*/
	public function UploadPhoto()
	{
		
		//检查文件格式
		$name=$_FILES[self::En_Photo_FILE]['name'];
		$allowtype=array('png','gif','bmp','jpeg','jpg');
		$aryStr=explode(".",$name);
		$filetype=strtolower($aryStr[count($aryStr)-1]);
		if(in_array(strtolower($filetype),$allowtype)){
			$id = Session::get("user.id");
			$src = Document::Upload(self::En_Photo_FILE, self::En_Photo_UPLOAD,Session::get("user.id"));
			$check=User::where('id','=',$id)->Update([
				"photo"=>$src
				]);
			if(1==$check){
				Session::set("user.photo",$src);
				Response::out(200,$src);
			}else{
				Response::out(410);
			}	
		}else{
				Response::out(421);
		}

	}
	
	/**官网-发送邮件
	*
	*@param string $mail 	邮箱
	*@param string $nickname		昵称(16内)
	*@param string $password     密码 
	*@param string $password2    确认密码
	*
	*@return status.状态码 
	*/
	public function sendEmail($mail,$nickname,$password,$password2)
	{
		//检查邮件格式
		if(!preg_match("/([\w\-]+\@[\w\-]+\.[\w\-]+)/",$mail))
		{
			Response::out(409);
			return false;
		}
		//检查邮件的唯一性
		if(User::where('mail','=',$mail)->select('mail'))
		{
			Response::out(408);
			return false;
		}
		
		//检查密码是否输入一致					
		if($password!=$password2){
			Response::out(414);
			return false;
		}
		//检查输入是否超出限制
		if(strlen(Html::removeSpecialChars($nickname))>51||strlen($mail)>51||strlen(Html::removeSpecialChars($password))>60){
			Response::out(312);
			return false;
		}
		//检查密码长度
		if(strlen($password)<6){
			Response::out(418);
			return false;
		}
		//检查是否已发送邮件			
		if(Cache::has(md5($mail))){
			Response::out(419);
			return false;
		}
 		if(Verify::auth()){														
			$token=md5($mail);
			$array=[
				"nickname" =>Html::removeSpecialChars($nickname),
				"mail"	   =>$mail,
				"password" =>$password,
				"token"    =>$token,
				"time"     =>time()+30*60
			];		
			Cache::set($token ,json_encode($array),1800);															
			$emailbody = "亲爱的".$nickname."：<br/>感谢您在我站注册了新帐号。<br/>请点击链接激活您的帐号。<br/>
（注：链接有效时间为30分钟，超时链接失效请重新进行申请操作）<br/>
<a href='https://api.wangyuan.info:4433/Enroll/Register?token=".$token."' target= 
'_blank'>https://api.wangyuan.info:4433/Enroll/Register?token=".$token."</a><br/> 
如果以上链接无法点击，请将它复制到你的浏览器地址栏中进入访问"; 
			Mail::to($mail)->title("WangYuanStudio")->content($emailbody);				
			Response::out(200);			
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
	
	/**官网-解除限制账号
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
		if(!preg_match("/([\w\-]+\@[\w\-]+\.[\w\-]+)/",$mail))
		{
			Response::out(409);
			return false;
		}
		$checkdata=0;
		//获取用户的信息		
		if($data=User::where('mail','=',$mail)->select())
		{
			$user_nickname=$data[0]['nickname'];

		}else{
			Response::out(412);
			return false;
		}
		if(Cache::has($mail)){
			Response::out(419);
			return false;
		}
		$token=rand(100000,999999);
		$array=[
			"token" => $token,
			"time"  =>time()+120,
			"mail"  =>$mail
		];
		Cache::set([
			$token =>json_encode($array),
			$mail  =>$array['mail']
			],120);		
		$emailbody ="亲爱的".$user_nickname."，您好"."：<br/>验证码为".$token; 
		Mail::to($mail)->title("WangYuanStudio")->content($emailbody);
		Response::out(200);
			
	}

	/**官网-找回密码之修改密码
	*
	*@param string $password    	密码
	*@param string $password2		确认密码  
	*@param string $token           邮箱的验证码 
	*
	*@return status.状态码
	*/
	public function Supdatepsw($password,$password2,$token=NULL)
	{
		
		//判断是否已发送邮箱	
		if(!Cache::has($token)){
			Response::out(424);
			return false;
		}
		//获取缓存数据
		$array=json_decode(Cache::get($token));		
		foreach ($array as $key => $value) {
			if('time'==$key)
				$time=$value;
			if('token'==$key)
				$token_verify=$value;
			if('mail'==$key)
				$mail=$value;
		}
		//检查时间
		if(time()>$time){
			Response::out(413);
			return false;
		}
		//检查token
		if($token!=$token_verify)
		{
			Response::out(411);
			return false;
		}
		//检查密码
		if($password!=$password2)
		{
			Response::out(414);
			return false;
		}
		//检查密码长度
		if(strlen($password)<6){
			Response::out(418);
			return false;
		}
		//检查长度
		if(strlen(Html::removeSpecialChars($password))>60)
		{
			Response::out(312);
			return false;
		}
		$password=Html::removeSpecialChars($password);						
		$password=password_hash($password,PASSWORD_BCRYPT,['cost'=>mt_rand(7,10)]);
		$update_psw=User::where('mail','=',$mail)->Update([
		"password" =>$password
		]);
		if(1==$update_psw)
		{
			Cache::delete([$token,$mail]);
			Response::out(200);
		}else{
			Response::out(415);
		}
				
	}

	/**官网-修改密码
	*
	*@param string $password1		新密码
	*@param string $password2		确认密码
	*@param string $re_verify       验证码
	*
	*@return status.状态码
	*/
	public function Updatepsw($password1,$password2,$re_verify)
	{
		$uid = 	Session::get("user.id");
		//验证验证码	
		if(!$this->Updateverify($re_verify))
		{
			Response::out(302);
			return false;
		}
		//验证密码
		if($password1!=$password2){
			Response::out(414);
			return false;
		}	
		//验证密码长度
		if(strlen($password1)<6){
			Response::out(418);
			return false;
		}
		//验证密码长度
		if(strlen(Html::removeSpecialChars($password1))>60){
			Response::out(312);
			return false;
		}
		$password1=Html::removeSpecialChars($password1);											
		$password1=password_hash($password1,PASSWORD_BCRYPT,['cost'=>mt_rand(7,10)]);
 		$update_psw=User::where('id','=',$uid)->Update([
		"password" =>$password1
		]);
		if(1==$update_psw){
			Cache::delete($re_verify."send_verify");
			$out=new Login();
			$out->logout();
		}else{
			Response::out(415);
		}
					
	}

	/**官网-修改个人信息
	*
	*@param string $nickname		昵称(16内)
	*
	*@return status.状态码
	*/

	public function Updateuser($nickname)
	{
		if(strlen(Html::removeSpecialChars($nickname))>51){	
			Response::out(312);
			return false;
		}
		$uid = 	Session::get("user.id");					
		$update_user=User::where('id','=',$uid)->Update([					
			"nickname" => Html::removeSpecialChars($nickname)
			]);	
		if(1==$update_user){
			Session::set("user.nickname",$nickname);				
			Response::out(200);
		}else{
			Response::out(417);
		}									 				
	}

	/**官网-修改密码之发送邮箱
	*
	*@return status.状态码
	*/
	public function Sendverify()
	{	
		$mail=Session::get("user.mail");
		$nickname=Session::get("user.nickname");
		$verify=rand(100000,999999);
		$emailbody = "亲爱的".$nickname."，您好"."：<br/>验证码为".$verify;   
		if(Cache::has($mail)){
			Response::out(419);
		}else{
			Cache::set($verify."send_verify",$verify,120);  
			Cache::set($mail,$mail,120);					   
			Mail::to($mail)->title("WangYuanStudio")->content($emailbody);	
			Response::out(200);
		}	
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





	public function ds(){
		Cache::flush();
	}

	public function test($token,$mail){
		response(__ROOT__);
		response(Cache::get($token));
		response(time());
		//Cache::delete($token);
		Cache::delete($mail);
		
	}
}


