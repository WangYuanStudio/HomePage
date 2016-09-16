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

class Sign
{
	const En_Photo_REGISTER='avatar/head.gif';
	const HW_DEPARTMEMT = ['backend', 'frontend', 'design', 'secret'];	// 部门标识符
	// public $middle = [
	// 	'Insertnews' => 'Check_login',
	// 	'Signupreview' => ['Check_login','Check_ManagerMember'],
	// 	'CheckPower' =>['Check_login','Check_ManagerMember'],
	// 	'Allpass' =>['Check_login','Check_ManagerMember'],
	// 	'Alldelete' =>['Check_login','Check_ManagerMember'],
	// 	'Elimination' =>['Check_login','Check_ManagerMember'],
	// 	'Content_search'=>['Check_login','Check_ManagerMember'],
	// 	'Addsigntime'=>['Check_login','Check_ManagerMember'],
	// 	'Getsigntime'=>['Check_login','Check_ManagerMember'],
	// 	'Deletesigntime'=>['Check_login','Check_ManagerMember'],
	// 	// 对所有方法判断登录
	// ];

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
		428 => 'The phone number has been in existence'
	];

	/**报名系统-实现报名
	*@param string $name    	名字(10内)
	*@param string $sid          学号
	*@param enum $department 	部门名称{'backend','frontend','design','secret'}
	*@param enum $grade 		年级{'2015级','2016级'}
	*@param string $phone 		长号	
	*@param string $short_phone 	短号
	*@param string $sex    			性别
	*@param string $college      学院(16内)
	*@param string $major        专业(16内)
	*@param string $mail 0 邮箱(电脑不用，移动端必须)
	*@param string $token 0 短信验证码(电脑不用，移动端必须)
	*
	*@return status.状态码  
	*/

	public function Insertnews($name,$sid,$department,$grade,$phone,$short_phone,$sex,$college,$major,$mail=NULL,$token=NULL)
	{
		if (!in_array($department, self::HW_DEPARTMEMT)) {
			Response::out(500);
			return false;
		}
		$uid=Session::get("user.id");
		$truedata=0;
		$check_uid=0;
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
		$data=Info::where('sid','=',$sid)->select('sid');					
		foreach($data as $value){
			if (in_array($sid , $value)){
				$truedata=1;				
			}
		}
		//查询是否已报名
		$search_data=Info::where('uid','=',$uid)->select('uid');
		foreach($search_data as $value){
			if(in_array($uid, $value)){
				$check_uid=1;
			}
		}
		//学号
		if(1==$truedata)
		{
			Response::out(403);
			return false;
		}
		//已报名
		if(1==$check_uid){
			Response::out(420);
			return false;
		}	
		//长度判断
		if(strlen(Html::removeSpecialChars($name))>30||strlen($sid)>11||strlen($department)>15||strlen(Html::removeSpecialChars($grade))>20||strlen($sex)>3||strlen(Html::removeSpecialChars($college))>50||strlen(Html::removeSpecialChars($major))>50||strlen($phone)>11||strlen($short_phone)>6)	
		{
			Response::out(312);
			return false;
		}
		//phone user set 
		if(is_null($uid)){
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
			$checkdata=0;
			$data=User::where('mail','=',$mail)->select('mail');
			foreach($data as $value){
				if(in_array($mail, $value)){
					$checkdata=1;
				}
			}
			if(1==$checkdata)
			{
				Response::out(408);
				return false;
			}
			//检查长号的唯一性
			$checkphone=0;
			$data_phone=Info::where('phone','=',$phone)->select('phone');
			foreach($data_phone as $value){
				if(in_array($phone, $value)){
					$checkphone=1;
				}
			}
			if(1==$checkphone)
			{
				Response::out(428);
				return false;
			}
			//判断短信
			if(!$this->Judgemessage($phone,$token)){
				return false;
			}

			$password=password_hash($phone,PASSWORD_BCRYPT,['cost'=>mt_rand(7,10)]);
			$register_uid=User::Insert([
			"nickname" =>'intern'.rand(10000,99999),
			"mail" =>$mail,
			"password"=>$password,
			"photo" =>self::En_Photo_REGISTER,
			"role" =>10,
			"status"=>0
			]);	
			$uid=$register_uid[0];
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
			// if(Verify::auth()){
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
				Response::out(200);
			// }	
		}
			
	}

	/**报名系统-确认报名审核通过或不通过
	*
	*@param int $uid  用户id
	*@param int $privilege 0 审核判断,默认为1通过,2为不通过  
	*
	*@return status.状态码
	*/
	public function Signupreview($uid,$privilege=1)
	{
		$checkdata=0;
		$department='department';
		$data=Info::where('uid','=',$uid)->select();
		foreach($data as $key => $value){
			if(in_array($uid,$value)){
				$checkdata=1;
				$checkvalue=$value;
				if(1==$checkdata){
					foreach($checkvalue as $key =>$value){
						if($key=='department')
							$department=$value;
						}
				}
			}
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

	/**报名系统-获取报名列表
	*
	*@param int $page 0   页码
	*@param enum $department 0 部门名称{'backend','frontend','design','secret'}
	*@param int $privilege 0 审核判断,默认为0未审核,1通过,2为不通过
	*
	*@return status.状态码 data.指定页的审核数据 num.总页数
	*/

	public function CheckPower($page=1,$department=null,$privilege=0)
	{
		Session::set("page_data",$page);	
		if($department!=null){
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

	/**报名-一键通过审核(本页)
	*
	*@return status.状态码
	*/
	public function Allpass()
	{				
		$data_info=Info::where('privilege','=',0)
				->limit((Session::get("page_data")-1)*4,4)
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
		Session::remove("page_data");
		Response::out(200);
	}

	/**报名-一键删除报名(全部未审核)
	*
	*@return status.状态码
	*/
	public function Alldelete()
	{					
		Info::where('privilege','=',0)->delete();			
		Response::out(200);
	}

	/**报名-审核淘汰
	*
	*@param int $uid    用户id
	*
	*@return status.状态码
	*/
	public function Elimination($uid)
	{
		Info::where('uid','=',$uid)->delete();	
		User::where('id','=',$uid)->update([
						"role"	=>10
				]);
		Response::out(200);
	}

	/**报名-搜索
	*
	*@param int $page 0   页码
	*@param string $content    内容
	*
	*@return status.状态码 data.指定页的搜索数据 num.总页数
	*/
	public function Content_search($page=1,$content)
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

	/**报名-设置报名时间
	*
	*@param timestamp $end_time 1 截止时间（13位时间戳）
	*@param timestamp $start_time 0 起始时间（13位时间戳）
	*
	*@return status.状态码
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

	/**报名-获取所有报名时间
	*@param int $page 0   页码
	*	
	*@return status.状态码 data.指定页的报名时间 num.总页数
	*/
	public function Getsigntime($page=1)
	{
		$data=Info_Time::limit(($page - 1) * 10, 10)->select();	
		$num=ceil(count(Info_Time::limit(($page - 1) * 4, 4)->select())/4);
 		if(0==count($data)){
 			Response::out(311);
 		}else{
 		Response::out(200,['data'=>$data,'num'=>$num]);    
 		}
	}

	/**报名-删除报名时间
	*@param int $time_id    时间id
	*	
	*@return status.状态码 
	*/
	public function Deletesigntime($time_id)
	{
		Info_Time::where('time_id','=',$time_id)->delete();
		Response::out(200);
	}

	/**报名-获取最新报名时间
	*	
	*@return status.状态码 data.报名时间
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

	/**报名-修改报名时间
	*@param int $time_id    时间id
	*@param timestamp $end_time 1 截止时间（13位时间戳）
	*@param timestamp $start_time 0 起始时间（13位时间戳）
	*	
	*@return status.状态码 
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

	/**报名-列表通过/否决
	*@param array $data    列表数据(名字，名字，逗号是中文的！！)
	*@param int $privilege 0 审核判断,默认为1通过,2为不通过  
	*	
	*@return status.状态码 
	*/
	public function Listpass($data,$privilege=1)
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

	/**发送短信(60s)
	*@param string $phone   长号
	*
	*@return status.状态码 
	*/
	public function sendPhone($phone){
		$verify=rand(100000,999999);
		$array=[
			"verify" => $verify,
			"time"  =>time()+120
		];
		//判断缓存
		if(Cache::has($phone)){
			Response::out(425);
			return false;
		}
		//send message
		if(Message::send($phone, $verify)){       	
			Cache::set($phone,json_encode($array),80);	
        	Response::out(200);
      	}
	}

	/**判断短信
	*
	*@param string $phone   长号
	*@param string $token   短信验证码
	*
	*@return status.状态码 
	*/
	public function Judgemessage($phone,$token=NULL){
		//判断是否已发送短信	
		if(!Cache::has($phone)){
			Response::out(426);
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
	
	public function test(){
		if($this->Judge_phone()){
			response("phone");
		}else{
			response("PC");
		}
		Cache::delete('13640134362');
	}
	
}