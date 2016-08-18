<?php
namespace App\lib;

/**
*Copyright @WangYuanStudio
*
*Author:Yaopeihong
*Last modified time: 2016-08-03 21:41
*
*/
class Document{
	/**
	*传过来的文件名
	*@name   string
	*/
	private static $name;   

	/**
	*传过来的文件路径+'/'+$name
	*@paths   string
	*/   
	private static $paths;	

	/**
	*传过来的文件类型，如rar，zip，7z
	*@type   string
	*/
	private static $allowtype=array('zip','rar','7z','png','gif','bmp','ipeg','jpg');

	/**
	*设置文件名+时间
	*@type   string
	*/
	private static $fileName;

	/**
	*获取文件后缀
	*/
	private static $filetype; 	

	/**
	*获取文件大小
	*/	
	private static  $size;

	public static function Upload($files,$path,$fileoldname=NULL){
		self::$size=$_FILES[$files]['size'];
		//判断文件大小是否符合要求
		if(self::checkFileSize()){	
			if($_FILES[$files]['error']>0){
				return false;
			}		
			self::$name=$_FILES[$files]['name'];
			self::$paths=$path;					
		}else{
			return false;
		}		
		//判断路径是否存在，不存在则创建		
		if(!file_exists(self::$paths)){
			mkdir(self::$paths,0755);
		}
		//判断格式
		if(self::checkFileType())
		{//设置文件名
			self::setFileName($fileoldname); 
			//判断文件是否保存成功，成功则返回文件信息
			if(move_uploaded_file($_FILES[$files]['tmp_name'],self::$paths.self::$fileName)){			
				return self::$paths.self::$fileName;
			}
			else{
					return false;
				}			
			}	
		return false;
	}

	/**
	*判断文件大小是否超出10M
	*/
	private static function checkFileSize(){
		if(self::$size<=1000000){
			return true;
		}else{
			return false;
		}
	}
	/**
	*获取文件类型，并判断是否为zip,rar,7z,jpg,ipeg,gif,png
	*/
	private static function checkFileType(){
		$aryStr=explode(".",self::$name);
		self::$filetype=strtolower($aryStr[count($aryStr)-1]);
		if(in_array(strtolower(self::$filetype),self::$allowtype)){
			return true;
		}else{
			return false;
		}
	}

	/**
	*设置文件名
	*/
	private static function setFileName($oldname=NULL){
		//判断是否有传参，默认为null，有传则按传参命名，无则按时间就随机数命名
		if(!is_null($oldname))
		{
			self::$fileName=$oldname.'.';
			self::$fileName.=self::$filetype;			
		}else{
		self::$fileName.=date('YmdHis')."_".rand(100,999).'.';
		self::$fileName.=self::$filetype;
		}
	}
}
