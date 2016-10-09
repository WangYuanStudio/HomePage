<?php
/*
*User: huizhe
*Date: 2016/9/6
*Time: 21:22
*/

namespace App\Controllers;

use App\Models\User;
use App\Models\Info;
use App\Models\Info_Time;
use App\Lib\Response;
use App\Lib\Html;
use App\Controllers\Common;
use App\Lib\Message;
use App\Lib\Verify;
use App\Lib\Mail;

class Sign
{
	const En_Photo_REGISTER='avatar/head.gif';
	const HW_DEPARTMEMT = ['backend', 'frontend', 'design', 'secret'];	// 部门标识符
	public $middle = [
		'Insertnews' => 'Check_login',
		'Signupreview' => ['Check_login','Check_ManagerMember'],
		'CheckPower' =>['Check_login','Check_ManagerMember'],
		'Allpass' =>['Check_login','Check_ManagerMember'],
		'Alldelete' =>['Check_login','Check_ManagerMember'],
		'Elimination' =>['Check_login','Check_ManagerMember'],
		'Content_search'=>['Check_login','Check_ManagerMember'],
		'Addsigntime'=>['Check_login','Check_ManagerMember'],
		'Getsigntime'=>['Check_login','Check_ManagerMember'],
		'Deletesigntime'=>['Check_login','Check_ManagerMember'],
		'sendPhone'  =>'Check_Operation_Count',
		'Getuid'     =>'Check_login'
		// 对所有方法判断登录
	];
	public static $status=[
		401 => 'Mobile phone trombone error.',
		402 => 'Student id error.',
		403 => 'The student id already exists.'	,
		404 => 'An error occurred when audit failure.',
		408 => 'Your email has been in existence.',
		409 => 'Email format error.',
		411 => 'Token error.',
		413 => 'Time out.',
		420 => 'You have successfully registered.',
		422 => 'Registration deadline.',
		// 截止时间应大于现在和起始时间
		505 => 'The deadline should be greater than nowtime and start time.',
		500 => 'Illegal department.',	// 不存在该部门
		425 => 'message has been sent, please wait a moment.',
		426 => 'Please send back the message.',		
		427 => 'Please fill in the email.',
		428 => 'The phone number has been in existence',
		429 => 'Invalid format of cornet.',
		430 => 'Invalid sex.',
		431 => 'token is null.'
	];

	/**电脑实现报名
	* @group 报名
	* @header string authentication 口令认证
	*
	*@param string $name    	名字(10内)
	*@param string $sid          学号
	*@param enum $department 	部门名称{'backend','frontend','design','secret'}
	*@param enum $grade 		年级{'2015级','2016级'}
	*@param string $phone 		长号	
	*@param string $short_phone 	短号
	*@param string $sex    			性别('男','女')
	*@param string $college      学院(16内)
	*@param string $major        专业(16内)
	*@param string $mail 0 邮箱(电脑不用，移动端必须)
	*
	* @return status:状态码 errmsg:失败时,错误信息 
	* @example 成功 {"status":200,"data":null}
	* @example 没有登陆 {"status":300,"errmsg":"Invalid login status."}
	* @example 超过限制长度 {"status":312,"errmsg":"More than field limits."}
	* @example 手机长号格式不正确 {"status":401,"errmsg":"Mobile phone trombone error."}
	* @example 学号格式不正确 {"status":402,"errmsg":"Student id error."}
	* @example 学号已存在，请勿重复报名 {"status":403,"errmsg":"The student id already exists."}
	* @example 邮箱已存在 {"status":408,"errmsg":"Your email has been in existence."}
	* @example 邮箱格式错误 {"status":409,"errmsg":"Email format error."}
	* @example 密钥验证过或错误 {"status":411,"errmsg":"Token error."}
	* @example 您已成功报名 {"status":420,"errmsg":"You have successfully registered."}
	* @example 报名已截止 {"status":422,"errmsg":"Registration deadline."}
	* @example 邮箱为空 {"status":427,"errmsg":"Please fill in the email."}
	* @example 该手机已被注册过 {"status":428,"errmsg":"The phone number has been in existence."}
	* @example 无效的短号格式 {"status":429,"errmsg":"Invalid format of cornet."}
	* @example 无效的性别 {"status":430,"errmsg":"Invalid sex."}
    * @example 不存在该部门 {"status":500,"errmsg":"Illegal department."}
    */

	public function Insertnews($name,$sid,$department,$grade,$phone,$short_phone,$sex,$college,$major,$mail=NULL)
	{
		if (!in_array($department, self::HW_DEPARTMEMT)) {
			Response::out(500);
			return false;
		}
		
		if($sign_time=Info_Time::orderBy("time_id desc")->limit(0, 1)->select()){
			if (time() < strtotime($sign_time[0]['start_time']) ||
			time() > strtotime($sign_time[0]['end_time'])){
				Response::out(422);
				return false;
			}
		}else{
			return false;
		}
		//验证手机号
		if(!preg_match('#^13[\d]{9}$|^14[5,7]{1}\d{8}$|^15[^4]{1}\d{8}$|^17[0,6,7,8]{1}\d{8}$|^18[\d]{9}$#', $phone)){
			Response::out(401);
			return false;
		}
		//验证学号11位
		if(!preg_match('/^\d{11}$/', $sid)){
			Response::out(402);
			return false;
		}
		//查询学号是否存在
		if(Info::where('sid','=',$sid)->select('sid'))					
		{
			Response::out(403);
			return false;
		}		
		//检查长号的唯一性
		if(Info::where('phone','=',$phone)->select('phone'))
		{
			Response::out(428);
			return false;
		}
		
		//检查短号
		if(!is_numeric($short_phone)){
			Response::out(429);
			return false;
		}

		//检查性别
		if (!in_array($sex,['男','女'])) {
			Response::out(430);
			return false;
		}

		//长度判断
		if(strlen(Html::removeSpecialChars($name))>30||strlen($sid)>11||strlen($department)>15||strlen(Html::removeSpecialChars($grade))>20||strlen($sex)>3||strlen(Html::removeSpecialChars($college))>50||strlen(Html::removeSpecialChars($major))>50||strlen($phone)>11||strlen($short_phone)>6)	
		{
			Response::out(312);
			return false;
		}
		//phone user set 
		if(!$this->Getuid()){
			//judge PC
			if(!$this->Judge_phone()){
				Response::out(300);
				return false;
			}

			if(is_null($mail))
			{
				Response::out(427);
				return false;
			}
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

			//判断短信

			if(null==Session::get("phone")){
				Response::out(411);
				return false;
			}
			//register
			$password=password_hash($sid,PASSWORD_BCRYPT,['cost'=>mt_rand(7,10)]);
			$register_uid=User::Insert([
			"nickname" =>'intern'.rand(10000,99999),
			"mail" =>$mail,
			"password"=>$password,
			"photo" =>self::En_Photo_REGISTER,
			"role" =>10,
			"status"=>0
			]);	
			$uid=$register_uid[0];
			//sign
			$insert_news=Info::insert([
				"uid" 		 	=> $uid,
				"name" 			=> Html::removeSpecialChars($name),
				"sid"			=> $sid,
				"department" 	=> Html::removeSpecialChars($department),
				"grade"			=> Html::removeSpecialChars($grade),
				"phone"			=> $phone,
				"short_phone"	=> $short_phone,
				"privilege"     => 0,
				"sex"			=> $sex,
				"college"       => Html::removeSpecialChars($college),
				"major"			=> Html::removeSpecialChars($major)
				]);
			Cache::delete($phone);
			Response::out(200);
		}
		else{
				$uid=$this->Getuid();
				//查询是否已报名
				if(Info::where('uid','=',$uid)->select('uid'))
				{
					Response::out(420);
					return false;
				}
			 if(Verify::auth()){
				$this->PCsign($uid,$name,$sid,$department,$grade,$phone,$short_phone,$sex,$college,$major);
				Response::out(200);
			 }	
		}
			
	}

	/**确认报名审核通过或不通过
	*
	*@group 报名
	* 
	*@param int $uid  用户id
	*@param int $privilege  审核判断,为1通过,2为不通过  
	*
	*@return status:状态码 errmsg:失败时,错误信息 
	*@example 成功 {"status":200,"data":null}
	*@example 审核失败 {"status":404,"errmsg":"An error occurred when audit failure."}
	*/
	public function signupreview($uid,$privilege)
	{
		
		if($data=Info::where('uid','=',$uid)->select()){
			$department=$data[0]['department'];
			//echo $department;
		}else{
			return false;
		}
		
		if('design'==$department){		
			$check=	User::where('id','=',$uid)->update([
					"role"	=>8
		]);
		}elseif('frontend'==$department){
			$check= User::where('id','=',$uid)->update([
					"role"	=>7
		]);
		}elseif('backend'==$department){
			$check= User::where('id','=',$uid)->update([
					"role"	=>9
		]);
		}else{
			$check= User::where('id','=',$uid)->update([
					"role"	=>6
		]);
		}
			$check_info=Info::where('uid','=',$uid)->update([
					"privilege"=>$privilege
		]);
		if(1==$check&&1==$check_info){
			// 消息通知
			if(1==$privilege){
				Common::setInform($uid, "报名", "报名成功", "您于".date("Y-m-d H:i:s")."报名审核通过，快去看看！", "");
			}else{
				User::where('id','=',$uid)->update([
						"role"	=>10
					]);	
				Common::setInform($uid, "报名", "报名失败", "您于".date("Y-m-d H:i:s")."报名审核不通过，快去看看！", "");
			}
			Response::out(200);
		}else{
			Response::out(404);
		}

	}

	/**获取报名列表
	*@group 报名
	*
	*@param int $page    页码
	*@param enum $department  部门名称{'backend','frontend','design','secret','all'}
	*@param int $privilege  审核判断,0未审核,1通过,2为不通过
	*
	*@return status:状态码 data.data:指定页的审核数据 data.num:总页数
	*@example 成功 {"status":200,"data":{"data":[{"uid":"1","name":"huizhe","sid":"15115071013","department":"backend","grade":"15\u8f6f\u5de51\u73ed","phone":"1","short_phone":"653362","privilege":"0","sex":null,"college":null,"major":null},{"uid":"6","name":"qwe","sid":"12345678913","department":"backend","grade":"213","phone":"1","short_phone":"653362","privilege":"0","sex":"\u7537","college":"ert","major":"asd"},{"uid":"11","name":"\u7070\u8005","sid":"15115071001","department":"backend","grade":"2015\u5e74\u7ea7","phone":"1","short_phone":"653362","privilege":"0","sex":"\u7537","college":"\u4fe1\u606f\u79d1\u5b66\u4e0e\u5de5\u7a0b\u5b66\u9662","major":"\u8f6f\u4ef6\u5de5\u7a0b"},{"uid":null,"name":"\u7070\u8005","sid":"15115071004","department":"backend","grade":"2015\u5e74\u7ea7","phone":"1","short_phone":"653362","privilege":"0","sex":"\u7537","college":"\u4fe1\u606f\u79d1\u5b66\u4e0e\u5de5\u7a0b\u5b66\u9662","major":"\u8f6f\u4ef6\u5de5\u7a0b"}],"num":2}}
	*/

	public function CheckPower($page,$department,$privilege)
	{	
		if($department!='all'){
		$data=Info::where('privilege','=',$privilege)
			->andwhere('department','=',$department)
			->limit(($page - 1) * 4, 4)
            ->select('*');
        $num=ceil(count(Info::where('privilege','=',$privilege)
			->andwhere('department','=',$department)
            ->select('*'))/4);
        }else{
        	$data=Info::where('privilege','=',$privilege)
			->limit(($page - 1) * 4, 4)
            ->select('*');
            $num=ceil(count(Info::where('privilege','=',$privilege)->select('*'))/4);
        }              
        Response::out(200,['data'=>$data,'num'=>$num]);       
	}

	/**一键通过审核(本页)
	*@group 报名
	*@param array $array      uid数组
	*
	*@return status:状态码
	*@example 成功 {"status":200,"data":null}
	*/
	public function Allpass($array)
	{				
		for($i=0;$i<count($array);$i++){				
			$data_info=Info::where('uid','=',$array[$i])
					->select('uid,department');
			foreach($data_info as $key => $value){
				$data_value=$value;						
				//获取学号			
				$uid=$data_value['uid'];			
				//获取部门
				$department=$data_value['department'];
														
				//修改角色
				if('design'==$department){		
					User::where('id','=',$uid)->update([
							"role"	=>8
					]);				
				}elseif('frontend'==$department){
					User::where('id','=',$uid)->update([
							"role"	=>7
					]);				
				}elseif('backend'==$department){
					User::where('id','=',$uid)->update([
							"role"	=>9
					]);
				}else{
					User::where('id','=',$uid)->update([
							"role"	=>6
					]);				
				}				
				//审核通过
				Info::where('uid','=',$uid)->update([
					"privilege"=>1
					]);
			 	//消息提醒
			 	Common::setInform($uid, "报名", "报名成功", "您于".date("Y-m-d H:i:s")."报名审核通过，快去看看！", "");
				
			 }
		}
		Response::out(200);
		// 
	}

	/**一键删除报名(全部未审核)
	*
	* @group 报名
	*@return status:状态码
	*@example 成功 {"status":200,"data":null}
	*/
	public function Alldelete()
	{					
		Info::where('privilege','=',0)->delete();			
		Response::out(200);
	}

	/**报名-审核淘汰
	*@group 报名
	* 
	*@param int $uid    用户id
	*
	*@return status:状态码
	*@example 成功 {"status":200,"data":null}
	*/
	public function Elimination($uid)
	{
		Info::where('uid','=',$uid)->delete();	
		User::where('id','=',$uid)->update([
						"role"	=>10
				]);
		Response::out(200);
	}

	/**搜索
	*@group 报名
	* 
	*@param int $page    页码
	*@param string $content    内容
	*
	*@return status:状态码 data.data:指定页的搜索数据 data.num:总页数
	*@example 成功 {"status":200,"data":{"data":[{"uid":"1","name":"huizhe","sid":"15115071013","department":"backend","grade":"15软工1班","phone":"1","short_phone":"653362","privilege":"1","sex":null,"college":null,"major":null},{"uid":"2","name":"huizhe1","sid":"15115071012","department":"backend","grade":"15软工1班","phone":"1","short_phone":"653362","privilege":"1","sex":null,"college":null,"major":null},{"uid":"3","name":"huizhe1","sid":"15115071011","department":"backend","grade":"15软工1班","phone":"1","short_phone":"653362","privilege":"1","sex":null,"college":null,"major":null},{"uid":"4","name":"huizhe","sid":"12345678901","department":"frontend","grade":"frontend","phone":"1","short_phone":"653362","privilege":"1","sex":null,"college":null,"major":null}],"num":3}}
	*@example 无数据 {"status":311,"errmsg":"Didnotfindrelevantcontent."}
	*/
	public function Content_search($page,$content)
	{
		$data=Info::where('name','like',"%".$content."%")
			->limit(($page - 1) * 4, 4)
 		          ->select();
 		$num=ceil(count(Info::where('name','like',"%".$content."%")->select())/4);		
 		if(0==count($data)){
 			Response::out(311);
 		}else{
 		Response::out(200,['data'=>$data,'num'=>$num]);    
 		}
	}

	//转换Javascript的时间戳
	private function getJSTimestamp($timestamp) {
		if (strlen($timestamp) == 13)	// JS的13位时间戳
			return substr($timestamp, 0, -3);
		else 	// 格式化时间
			return strtotime($timestamp);
	}

	/**设置报名时间
	*@group 报名
	*@param timestamp $end_time 1 截止时间（13位时间戳）
	*@param timestamp $start_time 0 起始时间（13位时间戳）
	*
	*@return status:状态码 errmsg:失败时,错误信息 data.tid:时间id
	*@example 成功 {"status":200,"data":{"tid":"6"}}
	*@example 截止时间应大于现在和起始时间 {"status":505,"errmsg":"The deadline should be greater than nowtime and start time."}
	*/
	public function Addsigntime( $start_time = null,$end_time)
	{
		$end_time = $this->getJSTimestamp($end_time);	// 转换时间戳
		$start_time = is_null($start_time) ? time() : $this->getJSTimestamp($start_time);
		if ($end_time < time() || $end_time < $start_time) {
			// 时间设置不符
			Response::out(505);
			return;
		}
		$time_id = Info_Time::insert([			
			'start_time' => date("Y-m-d H:i:s", $start_time),
			'end_time' => date("Y-m-d H:i:s", $end_time),			
		]);
		Response::out(200, ['tid' => $time_id[0]]);
	}

	/**获取所有报名时间
	*@group 报名
	*@param int $page   页码
	*	
	*@return status:状态码 errmsg:失败时,错误信息 data.data:指定页的报名时间 data.num:总页数
	*@example 成功 {"status":200,"data":{"data":[{"time_id":"2","start_time":"2016-09-0520:20:05","end_time":"2016-09-0600:00:00"},{"time_id":"3","start_time":"2016-09-0621:04:25","end_time":"2016-09-0800:00:00"},{"time_id":"4","start_time":"2016-09-1017:02:08","end_time":"2016-09-3000:00:00"},{"time_id":"5","start_time":"2016-10-0621:59:00","end_time":"2016-10-1021:59:00"}],"num":2}}
	*@example 无数据 {"status":311,"errmsg":"Didnotfindrelevantcontent."}
	*/
	public function Getsigntime($page=1)
	{
		$data=Info_Time::Limit(($page - 1) * 4, 4)->select();	
		$num=ceil(count(Info_Time::select())/4);
 		if(0==count($data)){
 			Response::out(311);
 		}else{
 		Response::out(200,['data'=>$data,'num'=>$num]);    
 		}
	}

	/**删除报名时间
	*@group 报名
	*@param int $time_id    时间id
	*	
	*@return status:状态码 
	*@example 成功 {"status":200,"data":null}
	*/
	public function Deletesigntime($time_id)
	{
		Info_Time::where('time_id','=',$time_id)->delete();
		Response::out(200);
	}

	/**获取最新报名时间
	*@group 报名
	*	
	*@return status:状态码 errmsg:失败时,错误信息 data.data:报名时间
	*@example 成功 {"status":200,"data":{"data":[{"time_id":"7","start_time":"2016-10-08 20:26:00","end_time":"2016-10-11 20:26:00"}]}}
	*@example 无数据 {"status":311,"errmsg":"Didnotfindrelevantcontent."}
	*/
	public function Getnewsigntime()
	{
		$data=Info_Time::limit(0, 1)->orderBy('time_id desc')->select();		
 		if(0==count($data)){
 			Response::out(311);
 		}else{
 		Response::out(200,['data'=>$data]);    
 		}
	}

	/**修改报名时间
	*@group 报名
	*@param int $time_id    时间id
	*@param timestamp $end_time 1 截止时间（13位时间戳）
	*@param timestamp $start_time 0 起始时间（13位时间戳）
	*@return status:状态码 errmsg:失败时,错误信息 
	*@example 成功 {"status":200,"data":null}
	*@example 截止时间应大于现在和起始时间 {"status":505,"errmsg":"The deadline should be greater than nowtime and start time."}
	*/
	public function Updatesigntime($time_id, $start_time = null,$end_time)
	{
		
		$end_time = $this->getJSTimestamp($end_time);	// 转换时间戳
		$start_time = is_null($start_time) ? time() : $this->getJSTimestamp($start_time);
		if ($end_time < time() || $end_time < $start_time) {
			// 时间设置不符
			Response::out(505);
			return;
		}
		Info_Time::where('time_id','=',$time_id)->Update([			
			'start_time' => date("Y-m-d H:i:s", $start_time),
			'end_time' => date("Y-m-d H:i:s", $end_time)			
		]);
		Response::out(200);
	}

	/**列表通过/否决
	*@group 报名
	*@param array $data    列表数据(名字，名字，逗号是中文的！！)
	*@param int $privilege  审核判断,为1通过,2为不通过  
	*	
	*@return status:状态码 
	*@example 成功 {"status":200,"data":null}
	*/
	public function Listpass($data,$privilege)
	{
		//切割成数组
		$array_data=explode("，", $data);
		//分割数据
		foreach ($array_data as $key => $value) {
			$info_data=Info::where('name','=',$value)->select();
			//防止重名
			for($i=0;$i<count($info_data);$i++)
			{
				$uid=$info_data[$i]['uid'];
				$department=$info_data[$i]['department'];
				//修改角色
				if('design'==$department){		
					User::where('id','=',$uid)->update([
							"role"	=>8
					]);				
				}elseif('frontend'==$department){
					User::where('id','=',$uid)->update([
							"role"	=>7
					]);				
				}elseif('backend'==$department){
					User::where('id','=',$uid)->update([
							"role"	=>9
					]);
				}else{
					User::where('id','=',$uid)->update([
							"role"	=>6
					]);				
				}				
				//审核
				Info::where('uid','=',$uid)->update([
					"privilege"=>$privilege
					]);
				// 消息通知
				if(1==$privilege){
					Common::setInform($uid, "报名", "报名成功", "您于".date("Y-m-d H:i:s")."报名审核通过，快去看看！", "");
				}else{
					User::where('id','=',$uid)->update([
							"role"	=>10
					]);	
					Common::setInform($uid, "报名", "报名失败", "您于".date("Y-m-d H:i:s")."报名审核不通过，快去看看！", "");
				}
			}
		}
		Response::out(200);
	}

	/**发送短信(120s)
	*@group 报名
	*@param string $phone   长号
	*
	*@return status:状态码 errmsg:失败时,错误信息 
	*@example 成功 {"status":200,"data":null}
	*@example 短信已发送 {"status":425,"errmsg":"message has been sent, please wait a moment."}
	*@example 该手机已被注册过 {"status":428,"errmsg":"The phone number has been in existence."}
	*/
	public function sendPhone($phone){
		//检查长号的唯一性
		if(Info::where('phone','=',$phone)->select('phone'))
		{
			Response::out(428);
			return false;
		}

		//判断缓存
		if(Cache::has($phone)){
			//获取缓存数据
			$array11=json_decode(Cache::get($phone));		
			foreach ($array11 as $key => $value) {
				if('re_time'==$key)
					$re_time=$value;
			}
			if(time()<=$re_time){
				Response::out(425);
				return false;
			}
		}
		

		$verify=rand(100000,999999);
		$array=[
			"verify" => $verify,
			"time"   =>time()+600,
			"re_time"=>time()+120
		];
		
		//send message
		if(Message::send($phone, $verify)){       	
			Cache::set($phone,json_encode($array),600);	
        	Response::out(200);
      	}
	}

	/**判断短信
	*@group 报名
	*@param string $phone   长号
	*@param string $token   短信验证码
	*
	*@return status:状态码 errmsg:失败时,错误信息 
	*@example 成功 {"status":200,"data":null}message has been sent, please wait a moment.
	*@example 短信已发送 {"status":411,"errmsg":"message has been sent, please wait a moment."}
	*@example 短信验证超时 {"status":413,"errmsg":"Time out."}
	*@example 请再次发送短信 {"status":426,"errmsg":"Please send back the message."}
	*@example 验证码为空 {"status":431,"errmsg":"token is null."}
	*/
	public function Judge_me($phone,$token=NULL){
		if($this->Judgemessage($phone,$token)){
			Session::set("phone",1);
		}
	}


	private function Judgemessage($phone,$token=NULL){
		//判断是否已发送短信	
		if(!Cache::has($phone)){
			Response::out(426);
			return false;
		}
		
		if(is_null($token)){
				Response::out(431);
				return false;
			}

		//获取缓存数据
		$array=json_decode(Cache::get($phone));		
		foreach ($array as $key => $value) {
			if('time'==$key)
				$time=$value;
			if('verify'==$key)
				$verify=$value;
		}
		if(time()>$time){
			Response::out(413);
			return false;
		}
		if($verify!=$token){
			Response::out(411);
			return false;
		}
		Response::out(200);		
		return true;		 
	}

	//判断移动端还是电脑端
	public function Judge_phone(){
		if(isset($_SERVER['HTTP_X_WAP_PROFILE'])) { 
	        return true; 
	    } 
	    if(isset ($_SERVER['HTTP_VIA'])) { 
	        //找不到为flase,否则为true 
	        return stristr($_SERVER['HTTP_VIA'], "wap") ?  true :  false; 
	    } 
	    if(isset($_SERVER['HTTP_USER_AGENT'])) { 
	        //此数组有待完善 
	        $clientkeywords = array ( 
	        'nokia', 
	        'sony', 
	        'ericsson', 
	        'mot', 
	        'samsung', 
	        'htc', 
	        'sgh', 
	        'lg', 
	        'sharp', 
	        'sie-', 
	        'philips', 
	        'panasonic', 
	        'alcatel', 
	        'lenovo', 
	        'iphone', 
	        'ipod', 
	        'blackberry', 
	        'meizu', 
	        'android', 
	        'netfront', 
	        'symbian', 
	        'ucweb', 
	        'windowsce', 
	        'palm', 
	        'operamini', 
	        'operamobi', 
	        'openwave', 
	        'nexusone', 
	        'cldc', 
	        'midp', 
	        'wap', 
	        'mobile'
	        ); 
	        // 从HTTP_USER_AGENT中查找手机浏览器的关键字 
	        if(preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) { 
	            return true; 
	        } 
	  
	    } 
	  
	    //协议法，因为有可能不准确，放到最后判断 
	    if (isset ($_SERVER['HTTP_ACCEPT'])) { 
	        // 如果只支持wml并且不支持html那一定是移动设备 
	        // 如果支持wml和html但是wml在html之前则是移动设备 
	        if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) { 
	            return true; 
	        } 
	    } 
		return false; 
	}
	/**get_uid
	 *
	 * @header string authentication 口令认证
	 */
	
	private function Getuid(){
		if(null==Session::get("user.id")){
			return false;
		}
		return Session::get("user.id");
	}
	//电脑端报名
	public function PCsign($uid,$name,$sid,$department,$grade,$phone,$short_phone,$sex,$college,$major){
		$insert_news=Info::insert([
				"uid" 		 	=> $uid,
				"name" 			=> Html::removeSpecialChars($name),
				"sid"			=> $sid,
				"department" 	=> Html::removeSpecialChars($department),
				"grade"			=> Html::removeSpecialChars($grade),
				"phone"			=> $phone,
				"short_phone"	=> $short_phone,
				"privilege"     => 0,
				"sex"			=> $sex,
				"college"       => Html::removeSpecialChars($college),
				"major"			=> Html::removeSpecialChars($major)
				]);
	}

	/**test
	 * 
	 * @return status:状态码 errmsg:失败时,错误信息 data.post_id:成功之后返回帖子的id
     * @example 200702 {"status":702,"errmsg":"Unavailable Department"}
     * @example 200200 {"status":200,"data":{"post_id":"38"}}
	 */
	public function test(){
		if($this->Judge_phone()){
			//response("phone");
		}else{
			//response("PC");
		}

		if(null==Session::get("phone")){
			//response("1");
		}
		else{
			//response("2");
		}
		//Mail::to("a22783276@163.com")->title("WangYuanStudio")->content("asd");	
		Cache::delete(md5("a22783276@163.com"));
		Response::out(200);
		//Cache::delete('13640134362');
		
	}
	/**test
	 *
	 * @param  [type] $id   id
	 * @param  [type] $name name
	 * @param  [type] $sex  sex
	 * @return [type]       [description]
	 */
	public function test1($id,$name,$sex){
		//response(200,"hello");
		Response::out(200,["ds"=>$id,"qw"=>$name,"er"=>$sex]);
		//echo "ID:" . $id;       
	}
}