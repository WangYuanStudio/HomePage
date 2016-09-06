<?php
/*
*User: huizhe
*Date: 2016/9/6
*Time: 21:22
*/

namespace App\Controllers;

use App\Models\Info;
use App\Models\Info_Time;
use App\Models\User;
use App\Lib\Response;
use App\Lib\Html;
use App\Controllers\Common;

class Sign
{
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
		// 对所有方法判断登录
	];

	public static $status=[
		401 => 'Mobile phone trombone error.',
		402 => 'Student id error.',
		403 => 'The student id already exists.'	,
		404 => 'An error occurred when audit failure.',
		420 => 'You have successfully registered.',
		422 => 'Registration deadline.',
		// 截止时间应大于现在和起始时间
		505 => 'The deadline should be greater than nowtime and start time.'
	];

	/**报名系统-实现报名
	*@param string $name    	名字(10内)
	*@param string $sid          学号
	*@param string $department 	部門
	*@param string $grade 		年级
	*@param string $phone 		长号	
	*@param string $short_phone 	短号
	*@param string $sex    			性别
	*@param string $college      学院(16内)
	*@param string $major        专业(16内)
	*
	*@return status.状态码  
	*/

	public function Insertnews($name,$sid,$department,$grade,$phone,$short_phone,$sex,$college,$major)
	{
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
		if(1==$truedata)
		{
			Response::out(403);
		}else{
			if(1==$check_uid){
			Response::out(420);
			}else{
				if(strlen(Html::removeSpecialChars($name))>30||strlen($sid)>11||strlen($department)>15||strlen(Html::removeSpecialChars($grade))>20||strlen($sex)>3||strlen(Html::removeSpecialChars($college))>50||strlen(Html::removeSpecialChars($major))>50||strlen($phone)>11||strlen($short_phone)>6)	
				{
					Response::out(312);
				}else{
					if(Verify::auth()){
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
					}	
				}
			}				
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
		if('页面部设计'==$department){		
			$check=	User::where('id','=',$uid)->update([
					"role"	=>8
		]);
		}elseif('页面部前端'==$department){
			$check= User::where('id','=',$uid)->update([
					"role"	=>7
		]);
		}elseif('编程部'==$department){
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
	*@param string $department 0 部门
	*@param int $privilege 0 审核判断,默认为0未审核,1通过,2为不通过
	*
	@return status.状态码 data.指定页的审核数据
	*/

	public function CheckPower($page=1,$department=null,$privilege=0)
	{
		Session::set("page_data",$page);	
		if($department!=null){
		$data=Info::where('privilege','=',$privilege)
			->andwhere('department','=',$department)
			->limit(($page - 1) * 10, 10)
            ->select('*');
        }else{
        	$data=Info::where('privilege','=',$privilege)
			->limit(($page - 1) * 10, 10)
            ->select('*');
        }              
        Response::out(200,['data'=>$data]);       
	}

	/**报名-一键通过审核(本页)
	*
	*@return status.状态码
	*/
	public function Allpass()
	{				
		$data_info=Info::where('privilege','=',0)
				->limit((Session::get("page_data")-1)*10,10)
				->select('uid,department');
		foreach($data_info as $key => $value){
			$data_value=$value;						
			//获取学号			
			$uid=$data_value['uid'];			
			//获取部门
			$department=$data_value['department'];											
			//修改角色
			if('页面部设计'==$department){		
				User::where('id','=',$uid)->update([
						"role"	=>8
				]);				
			}elseif('页面部前端'==$department){
				User::where('id','=',$uid)->update([
						"role"	=>7
				]);				
			}elseif('编程部'==$department){
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
	*@return status.状态码 data.指定页的搜索数据
	*/
	public function Content_search($page=1,$content)
	{
		$data=Info::where('name','like',"%".$content."%")
			->limit(($page - 1) * 10, 10)
 		          ->select();		
 		if(0==count($data)){
 			Response::out(311);
 		}else{
 		Response::out(200,['data'=>$data]);    
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

	/**报名-获取报名时间
	*@param int $page 0   页码
	*	
	*@return status.状态码 data.指定页的报名时间
	*/
	public function Getsigntime($page=1)
	{
		$data=Info_Time::limit(($page - 1) * 10, 10)->select();		
 		if(0==count($data)){
 			Response::out(311);
 		}else{
 		Response::out(200,['data'=>$data]);    
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
}