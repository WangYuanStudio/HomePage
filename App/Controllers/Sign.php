<?php
/*
*User: huizhe
*Date: 2016/8/22
*Time: 11:30
*/

namespace App\Controllers;

use App\Models\Info;
use App\Models\User;
use App\Lib\Response;

class Sign
{
	// public $middle = [
	// 	'Insertnews' => 'Check_login',
	// 	'Updateuser' => 'Check_login',
	// 	'CheckPower' => 'Check_login'
	// 	// 对所有方法判断登录
	// ];

	public static $status=[
		401 => 'Mobile phone trombone error.',
		402 => 'Student id error.',
		403 => 'The student id already exists.'	,
		404 => 'An error occurred when audit failure, update the mysql.'	
	];

	/**报名系统-获取报名数据
	*@param int $uid 			id
	*@param string $name    	名字
	*@param string $sid          学号
	*@param string $department 	部門
	*@param string $class 		班级
	*@param string $phone 		长号	
	*@param string $short_phone 	短号
	*@param string $Vcheckdata       验证码
	*
	*@return status.状态码  
	*/

	public function Insertnews($uid,$name,$sid,$department,$class,$phone,$short_phone,$Vcheckdata)
	{
		$truedata=0;
		//验证手机号
		if(preg_match('#^13[\d]{9}$|^14[5,7]{1}\d{8}$|^15[^4]{1}\d{8}$|^17[0,6,7,8]{1}\d{8}$|^18[\d]{9}$#', $phone)){
		//验证学号11位
			if(preg_match('/^\d{11}$/', $sid)){
				//查询学号是否存在
				$data=Info::where('sid','=',$sid)->select('sid');					
				foreach($data as $value){
					if (in_array($sid , $value)) {
						$truedata=1;				
					}}
				if(1==$truedata)
				{
					Response::out(403);
				}
				else{
					$v = Session::get("code")["text"];
       				 foreach ($Vcheckdata as $key => $value) {
            			if ($value["x"] > $v[ $key ]["max_x"]
                			|| $value["x"] < $v[ $key ]["min_x"]
                			|| $value["y"] > $v[ $key ]["max_y"]
                			|| $value["y"] < $v[ $key ]["min_y"]
            				) {
               				 Response::out(302);
               				die();
            				}
        			}       																										
						$insert_news=Info::insert([
						"uid" 		 	=>$uid,
						"name" 			=>$name,
						"sid"			=>$sid,
						"department" 	=>$department,
						"class"			=>$class,
						"phone"			=>$phone,
						"short_phone"	=>$short_phone,
						"privilege"     =>0
						]);
						Response::out(200);
						Session::remove("code");					
				}
			}else{
				Response::out(402);
			}
		}else{
			Response::out(401);
		}
	}

	/**报名系统-报名审核通过
	*
	*@param int $uid  用户id
	*@param string $department		部门
	*
	*@return status.状态码
	*/
	public function Updateuser($uid,$department)
	{
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
					"privilege"=>1
		]);
		if(1==$check&&1==$check_info){
			Response::out(200);
		}else{
			Response::out(404);
		}

	}

	/**报名系统-报名未审核
	*
	*@param int $page    页码
	*@param string $department		部门
	*
	@return status.状态码 data.指定页的帖子数据
	*/

	public function CheckPower($page=1,$department=null)
	{
		if($department!=null){
		$data=Info::where('privilege','=',0)
			->andwhere('department','=',$department)
			->limit(($page - 1) * 10, 10)
            ->select('*');
        }else{
        	$data=Info::where('privilege','=',0)
			->limit(($page - 1) * 10, 10)
            ->select('*');
        }       
        Response::out(200,['data'=>$data]);
	}
	
}