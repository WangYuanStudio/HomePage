<?php
/*
*User: huizhe
*Date: 2016/8/12
*Time: 13:18
*/

namespace App\Controllers;

use App\Models\User;
use App\Lib\Document;

 class Enroll
 {
	/**获取注册数据
	*@param string nickname		昵称
	*@param string mail         邮箱
	*@param string password     密码        
	*@param string photo 		头像  默认为 avatar/head.gif
	*
	*return status.返回插入的id或状态/错误代码
	*/
	public function Login($nickname,$mail,$password,$photo="avatar/head.gif")
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
				response(['status'=> -1,'msg' =>'邮箱已存在']);
			}else{
				$login_id=User::Insert([
					"nickname" =>$nickname,
					"mail" =>$mail,
					"password"=>$password,
					"photo" =>$photo,
					"role" =>5
					]);
				response(['status'=>$login_id]);
			}
		}else{
			response(['status'=>-2,'msg'=>'邮箱格式错误']);
		}
	}
	
	/**更换头像
	*@param int id 用户ID
	*@param string form_filename 头像上传组名
	*@param string path 头像路径，默认为avatar/
	*
	*return status.返回头像完整路径
	*/
	public function UploadPhoto($form_filename,$path='avatar/',$id)
	{
		$src = Document::Documents($form_filename, $path);
		User::where('id','=',$id)->Update([
				"photo"=>$src
				]);
		response(["status"=>$src]);	
	}
	

}