<?php
/*
*User: huizhe
*Date: 2016/8/8
*Time: 19:19
*/

namespace App\Controllers;

use App\Models\Info;

class Sign
{
	/**获取报名数据
	*@param int $uid 			id
	*@param string $name    	名字
	*@param string sid          学号
	*@param string apartment 	部門
	*@param string class 		班级
	*@param string phone 		长号	
	*@param string short_phone 	短号
	*
	*return status.返回插入的数据
	*/

	public function Insertnews($uid,$name,$sid,$apartment,$class,$phone,$short_phone)
	{
		$insert_news=Info::insert([
			"uid" 		 	=>$uid,
			"name" 			=>$name,
			"sid"			=>$sid,
			"apartment" 	=>$apartment,
			"class"			=>$class,
			"phone"			=>$phone,
			"short_phone"	=>$short_phone
			]);

		response(["status" =>$insert_news]);
	}

}