<?php
/*
*User: huizhe
*Date: 2016/8/11
*Time: 17:34
*/

namespace App\Controllers;

use App\Models\Info;
use App\Models\User;

class Sign
{
	/**获取报名数据
	*@param int $uid 			id
	*@param string $name    	名字
	*@param string sid          学号
	*@param string department 	部門
	*@param string class 		班级
	*@param string phone 		长号	
	*@param string short_phone 	短号
	*
	*return status.状态/错误代码  
	*/

	public function Insertnews($uid,$name,$sid,$department,$class,$phone,$short_phone)
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
					response(['status' => -3,'msg' =>'该学号已存在']);
				}
				else{
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
					response(['status' => 1,'msg' => '报名成功' ]);
				}
			}else{
				response(['status' => -2,'msg' =>'学号错误']);
			}
		}else{
			response(['status' => -1,'msg' => '手机长号错误']);
		}
	}

	/**报名审核通过
	*
	*@param int uid  用户id
	*
	*return status.返回true
	*/
	public function Updateuser($uid)
	{
		User::where('id','=',$uid)->update([
					"role"	=>3
		]);
		Info::where('uid','=',$uid)->update([
					"privilege"=>1
		]);
		response("true");

	}

	/**报名未审核
	*
	*@param int $page    页码
	*
	@return data.指定页的帖子数据
	*/

	public function CheckPower($page=1)
	{
		$data=Info::where('privilege','=',0)
			->limit(($page - 1) * 10, 10)
            ->select('*');

        response(["data" => $data]);
	}
}