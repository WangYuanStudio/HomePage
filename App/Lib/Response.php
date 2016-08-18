<?php
namespace App\Lib;

use Zereri\Lib\Register;

/*
 * Response Format
 * Copyright @ WangYuanStudio
 *
 * Author: laijingwu
 * Last modified time: 2016-08-18 14:54
 *
 * Usage method:
 * use App\Lib\Response;
 * 1. Response::out($code, $return = null, $rewrite = false)
 */
class Response
{
	/*响应返回
	 * 
	 * @param int $code 1 状态码
	 * @param mixed $return 0 200:data,other:errmsg
	 * @param boolean $rewrite 0 重写errmsg
	 * @return json
	 */
	public static function out($code, $return = null, $rewrite = false) {
		$classname = Register::get("class");
		if ($code == 200)
			$response = ['status' => $code, 'data' => $return];
		else if (array_key_exists($code, $c = config('common_status')))
			$response = ['status' => $code, 'errmsg' => $c[$code]];
		else
			$response = ['statuc' => $code, 'errmsg' => ($rewrite ? $return : $classname::$status[$code])];

		response($response);
	}
}
?>