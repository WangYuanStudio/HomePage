<?php
/*
*User: huizhe
*Date: 2016/8/29
*Time: 17:18
*/

namespace App\Controllers;

use App\Models\Info;
use App\Models\User;
use App\Lib\Response;
use App\Lib\Html;
use App\Controllers\Common;

class Sign
{
	// public $middle = [
	// 	'Insertnews' => 'Check_login',
	// 	'Signupreview' => ['Check_login','Check_ManagerMember'],
	// 	'CheckPower' =>['Check_login','Check_ManagerMember']
	// 	// 对所有方法判断登录
	// ];

	public static $status=[
		401 => 'Mobile phone trombone error.',
		402 => 'Student id error.',
		403 => 'The student id already exists.'	,
		404 => 'An error occurred when audit failure.',
		420 => 'You have successfully registered.'
	];

	/**报名系统-实现报名
	*@param string $name    	名字(10内)
	*@param string $sid          学号
	*@param string $department 	部門
	*@param string $class 		年级
	*@param string $phone 		长号	
	*@param string $short_phone 	短号
	*@param string $sex    			性别
	*@param string $college      学院(16内)
	*@param string $major        专业(16内)
	*
	*@return status.状态码  
	*/

	public function Insertnews($name,$sid,$department,$class,$phone,$short_phone,$sex,$college,$major)
	{
		$uid=Session::get("user.id");
		$truedata=0;
		$check_uid=0;
		//验证手机号
		if(preg_match('#^13[\d]{9}$|^14[5,7]{1}\d{8}$|^15[^4]{1}\d{8}$|^17[0,6,7,8]{1}\d{8}$|^18[\d]{9}$#', $phone)){
		//验证学号11位
			if(preg_match('/^\d{11}$/', $sid)){
				//查询学号是否存在
				$data=Info::where('sid','=',$sid)->select('sid');					
				foreach($data as $value){
					if (in_array($sid , $value)) {
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
				}
				else{
					if(1==$check_uid){
					Response::out(420);
					}else{
						if(strlen(Html::removeSpecialChars($name))>30||strlen($sid)>11||strlen($department)>15||strlen(Html::removeSpecialChars($class))>20||strlen($sex)>3||strlen(Html::removeSpecialChars($college))>50||strlen(Html::removeSpecialChars($major))>50||strlen($phone)>11||strlen($short_phone)>6)	
						{
							Response::out(304);
						}else{
							if(Verify::auth()){
								$insert_news=Info::insert([
								"uid" 		 	=> $uid,
								"name" 			=> Html::removeSpecialChars($name),
								"sid"			=> $sid,
								"department" 	=> Html::removeSpecialChars($department),
								"class"			=> Html::removeSpecialChars($class),
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
			}else{
				Response::out(402);
			}
		}else{
			Response::out(401);
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
	*@param int $page    页码
	*@param string $department 0 部门
	*@param int $privilege 0 审核判断,默认为0未审核,1通过,2为不通过
	*
	@return status.状态码 data.指定页的审核数据
	*/

	public function CheckPower($page=1,$department=null,$privilege=0)
	{
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

}