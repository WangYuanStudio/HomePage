<?php
/*
*User: huizhe
*Date: 2016/8/11
*Time: 22:34
*/

namespace App\Controllers;

use App\Models\User;


 class Enroll
 {
	/**获取注册数据
	*@param string nickname		昵称
	*@param string mail         邮箱
	*@param string password     密码        
	*@param string photo 		头像，默认为../head.gif
	*
	*return status.返回插入的id或状态/错误代码
	*/
	public function Login($nickname,$mail,$password,$photo="../head.gif")
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
	
	/**头像
	*@param string photo 头像路径
	*
	*return status.返回status
	*/
	public function UploadPhoto($id,$photo)
	{
		
		$da=User::where('id','=',$id)->Update([
				"photo"=>$photo
				]);
		response("status");
		
	}
}