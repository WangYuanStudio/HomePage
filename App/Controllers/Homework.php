<?php
namespace App\Controllers;

use App\Models\Hws_Record;
use App\Models\Hws_Task;
use App\Lib\Document;
use App\Controllers\Session;
use App\Lib\Response;
use App\Lib\PclZip;
use App\Lib\Authorization;

// test
use App\Models\User;

date_default_timezone_set('PRC');

/*
 * Homework
 * Copyright @ WangYuanStudio
 *
 * Author: laijingwu
 * Last modified time: 2016-08-25 22:28
 */
class Homework
{
	const HW_UPLOAD_FOLDER = 'upload/';	// 作业上传存储路径
	const HW_UNZIP_FOLDER = '/home/homework/';	// 优秀作业解压存储路径
	const HW_ALLOWDLOAD_FILEEXT = [	// 允许预览的文件扩展名
		'php', 'txt', 'md', 'html', 'htm', 'css', 'js', 'aspx', 'asp'
	];

	public $middle = [
		// 'getExcellentWorksWhere' => 'Hws_SeeWork',
		// 'getExcellentWorks' => 'Hws_SeeWork',
		// 'getWorksFromLogined' => 'Hws_SeeWork',
		// 'uploadWork' => 'Hws_WorkSubmit',
		// 'getWorksFromTid' => 'Hws_Management',
		// 'getAllWorks' => 'Hws_Management',
		// 'getWorksFromRid' => 'Hws_Management',
		// 'correctWork' => 'Hws_Management',
		// 'setExcellentWorks' => 'Hws_Management',
		// 'updateWork' => 'Hws_Management',
		// 'deleteWork' => 'Hws_Management',
		// 'addTask' => 'Hws_Management',
		// 'updateTask' => 'Hws_Management',
		// 'deleteTask' => 'Hws_Management',
		// 'setTaskOff' => 'Hws_Management',
		// 'all' => ['Check_login', 'Hws_SeeWork'] // 对所有方法判断登录
	];

	/*错误代码 && 错误信息
	 * 
	 * @var array
	 */
	public static $status = [
		500 => 'Illegal department.',	// 不存在该部门
		// 数据库操作中，作业数据插入失败
		501 => 'Homework data insert failed. Maybe error occurs in mysql operation.',
		502 => 'It is not the time to submit homework.',	// 作业还没有开放提交
		503 => 'Invalid tid.',	// 无效的任务
		504 => 'Invalid Rid.',	// 无效的作业
		// 截止时间应大于现在和起始时间
		505 => 'The deadline should be greater than nowtime and start time.',
		506 => 'Can\'t not see homeworks.',	// 无权限查看作业
		507 => 'Unzip homework failed.',	// 解压作业失败
		508 => 'The homework has not been extracted.',	// 作业还没有被解压
		// 打开文件夹失败，请注意系统是否具有权限
		509 => 'Open dir failed. Please notice the system permission.',
		510 => 'File does not exist.',	// 文件不存在
		511 => 'This file cannot be preview.'	// 文件不支持预览
	];

	/*登录用户Session数据
	 * 
	 * @var array
	 */
	private $loginedUser;

	public function __construct() {
		$test = User::where('id', '=', 1)->select();
		$this->loginedUser = $test[0];//Session::get('user');
	}

	/*作业系统 - 获取优秀作业
	 * 
	 * @param int $uid 用户ID
	 * @return array.优秀作业
	 */
	private function getExcellentWorksRaw($uid = 0, $tid = 0) {
		$raw = Hws_Record::where('recommend', '=', 1);
		if ($uid > 0)
			$raw = $raw->andWhere('uid', '=', $uid);
		if ($tid > 0)
			$raw = $raw->andWhere('tid', '=', $tid);
		return $raw->orderBy('time desc')->select();
	}

	/**作业系统 - 获取某用户/某任务优秀作业
	 * 
	 * @param int $uid 0 用户ID（默认当前登录用户，为0时则查任务的优秀作业）
	 * @param int $tid 0 任务ID（为空时则查用户的所有优秀作业）
	 * @return status.状态码 data/id.作业ID data/tid.任务ID data/uid.提交用户ID data/file_path.作业文件路径 data/time.提交时间 data/note.备注 data/score.评分 data/comment.评语 data/comment_uid.批改用户ID data/comment_time.批改时间 data/recommend.是否推荐 data/unpack_path.解压后路径
	 */
	public function getExcellentWorksWhere($uid = null, $tid = null) {
		Response::out(200, $this->getExcellentWorksRaw(
			is_null($uid) ? $this->loginedUser['id'] : $uid,
			is_null($tid) ? 0 : $tid
		));
	}

	/**作业系统 - 获取所有优秀作业
	 * 
	 * @return status.状态码 data/id.作业ID data/tid.任务ID data/uid.提交用户ID data/file_path.作业文件路径 data/time.提交时间 data/note.备注 data/score.评分 data/comment.评语 data/comment_uid.批改用户ID data/comment_time.批改时间 data/recommend.是否推荐 data/unpack_path.解压后路径
	 */
	public function getExcellentWorks() {
		Response::out(200, $this->getExcellentWorksRaw(0));
	}

	/**作业系统 - 获取当前登录用户的所有作业
	 * 
	 * @return status.状态码 data/id.作业ID data/tid.任务ID data/uid.提交用户ID data/time.提交时间 data/note.备注 data/score.评分 data/comment.评语 data/comment_uid.批改用户ID data/comment_time.批改时间 data/recommend.是否推荐
	 */
	public function getWorksFromLogined() {
		Response::out(200, Hws_Record::where('uid', '=', $this->loginedUser['id'])
			->select('id, tid, uid, time, note, score, comment, comment_uid, comment_time, recommend'));
	}
	
	/**作业系统 - 上传作业
	 *
	 * @param string $form_filename 1 文件上传组件名
	 * @param int $tid 1 任务ID
	 * @param string $note 0 作业备注
	 * @return status.状态码 errmsg.错误信息 data/rid.作业ID
	 */
	public function uploadWork($form_filename, $tid, $note = null) {
		$path = self::HW_UPLOAD_FOLDER;
		if ($task = Hws_Task::where('id', '=', $tid)->select()) {
			// 验证提交开放时间
			if (time() >= strtotime($task[0]['start_time']) &&
			time() <= strtotime($task[0]['end_time'])) {
				// 文件上传
				$src = Document::Upload($form_filename, $path);
				//$src = substr($src, strlen($path));
				if ($rid = Hws_Record::insert([
					'tid' => $tid,
					'uid' => 1,//$this->loginedUser['id'], // 当前登录用户
					'file_path' => $src,
					'time' => date("Y-m-d H:i:s", time()),
					'note' => $note
				])) {
					// 提交成功
					Response::out(200, ['rid' => $rid[0]]);
				} else {
					// 数据库出错 作业数据插入失败
					Response::out(501);
				}
			} else {
				// 时间不符
				Response::out(502);
			}
		} else {
			// tid不存在
			Response::out(503);
		}
	}

	/**作业系统 - 获取所有任务（游客可用）
	 * 
	 * @param enum $department 0 部门名称{'backend','frontend','design','secret'}
	 * @return status.状态码 data/id.任务ID data/title.标题 data/content.内容 data/department.部门 data/start_time.提交起始时间 data/end_time.提交截止时间
	 */
	public function getAllTasks($department = null) {
		if ($department)
			$raw = Hws_Task::where('department', '=', $department)->select();
		else
			$raw = Hws_Task::select();

		Response::out(200, $raw);
	}

	/**获取解压后优秀作业目录
	 * 
	 * @param int $rid 1 作业ID
	 * @return status.状态码 data/dir.目录 data/file.文件
	 */
	public function getUnzipWorkDir($rid) {
		$w = Hws_Record::whereAndWhere(['id', '=', $rid], ['recommend', '=', 1])-> select();
		if (!empty($w)) {
			if ($dir = $this->read_all_dir(self::HW_UNZIP_FOLDER.$w[0]['unpack_path'])) {
				var_dump($dir);
				Response::out(200, $dir);
			} else {
				Response::out(509);
			}
		} else {
			Response::out(508);
		}
	}

	/**获取优秀作业文件内容
	 * 
	 * @param string $path 1 文件路径
	 * @return status.状态码 data.文件内容
	 */
	public function getUnzipWorkFile($path) {
		// Windows下要转换 中文目录名/中文文件名
		// $path = iconv('utf-8', 'gb2312', $path);
		$path = self::HW_UNZIP_FOLDER.$path;
		if (is_file($path)) {
			$arr = explode('.', $path);
			$ext = end($arr);
			if (in_array($ext, self::HW_ALLOWDLOAD_FILEEXT)) {
				Response::out(200, file_get_contents($path));
			} else {
				Response::out(511);
			}
		} else {
			Response::out(510);
		}
	}

	/*遍历读取目录（注意权限问题）
	 * 
	 * @param string $dir 路径
	 * @return array
	 */
	private function read_all_dir($dir) {
		if (substr($dir, -1) == '/')
			$dir = substr($dir, 0, -1);

		$result = array();
		$handle = @opendir($dir);	// 避免报错
		if ($handle) {
			while (($file = readdir($handle)) !== false) {
				if ($file != '.' && $file != '..') {
					$cur_path = $dir.'/'.$file;
					if (is_dir($cur_path)) {
						//$result['dir'][$cur_path] = $this->read_all_dir($cur_path);
						// 为了不返回绝对路径
						$result['dir'][substr($cur_path, strlen(self::HW_UNZIP_FOLDER))] = $this->read_all_dir($cur_path);
					} else {
						$result['file'][] = $file;//为了不返回绝对路径，因此没用$cur_path;
					}
				}
			}
			closedir($handle);
		}
		return $result;
	}

	/* 下方为管理员/正式成员功能 */

	/**作业系统 - 获取某任务的所有作业（以下为作业管理权限可用）
	 * 
	 * @param int $tid 1 作业ID
	 * @return status.状态码 data/id.作业ID data/tid.任务ID data/uid.提交用户ID data/file_path.作业文件路径 data/time.提交时间 data/note.备注 data/score.评分 data/comment.评语 data/comment_uid.批改用户ID data/comment_time.批改时间 data/recommend.是否推荐 data/unpack_path.解压后路径
	 */
	public function getWorksFromTid($tid) {
		Response::out(200, Hws_Record::where('tid', '=', $tid)->select());
	}

	/**作业系统 - 获取所属部门的所有作业
	 * 
	 * @return status.状态码 data/id.作业ID data/tid.任务ID data/uid.提交用户ID data/file_path.作业文件路径 data/time.提交时间 data/note.备注 data/score.评分 data/comment.评语 data/comment_uid.批改用户ID data/comment_time.批改时间 data/recommend.是否推荐 data/unpack_path.解压后路径
	 */
	public function getAllWorks() {
		// 获取登录用户对应所有权限
		$permission = Authorization::getExistingPermission($this->loginedUser['role']);
		$department = [];

		// 获取角色具有哪个部门的管理权限
		foreach ($permission as $key => $value) {
			$value = $value['name'];
			if (substr($value, 0, strlen('manage_')) == 'manage_' && 
				substr($value, 0 - strlen('_homeworks')) == '_homeworks') {
				$tmp = substr($value, strlen('manage_'), 0 - strlen('_homeworks'));
				array_push($department, $tmp);
			}
		}

		// 防止实习生获取作业
		if (empty($department)) {
			Response::out(301);
			return;
		}

		// 所属部门任务对应的作业
		$w = Hws_Task::hasMany("getWork")->where('Hws_Task.department', '=', $department[0]);
		for ($i = 1; $i < count($department); $i++) {
			$w = $w->orWhere('Hws_Task.department', '=', $department[$i]);
		}
		Response::out(200, $w->select());
	}

	/**作业系统 - 获取单个作业
	 * 
	 * @param int $rid 1 作业ID
	 * @return status.状态码 data/id.作业ID data/tid.任务ID data/uid.提交用户ID data/file_path.作业文件路径 data/time.提交时间 data/note.备注 data/score.评分 data/comment.评语 data/comment_uid.批改用户ID data/comment_time.批改时间 data/recommend.是否推荐 data/unpack_path.解压后路径
	 */
	public function getWorksFromRid($rid) {
		Response::out(200, Hws_Record::where('id', '=', $rid)->select());
	}

	/**作业系统 - 批改作业
	 * 
	 * @param int $rid 1 作业ID
	 * @param char $score 1 等级/分数
	 * @param string $comment 1 评语
	 * @param tinyint $recommend 0 是否推荐（1/0）
	 * @return status.状态码 errmsg.错误信息 data/row.受影响条数
	 */
	public function correctWork($rid, $score, $comment, $recommend = 0) {
		// 验证rid
		if (Hws_Record::where('id', '=', $rid)->select()) {
			$row = Hws_Record::where('id', '=', $rid)->update([
				'score' => $score,
				'comment' => $comment,
				'comment_uid' => $this->loginedUser['id'],
				'comment_time' => date("Y-m-d H:i:s", time()),
				'recommend' => ($recommend != 0) ? 1 : 0
			]);
			Response::out(200, ['row' => $row]);
		} else {
			// rid无效
			Response::out(504);
		}
	}

	/**作业系统 - 设置优秀作业
	 * 
	 * @param int/array $rid 1 作业ID
	 * @return status.状态码 data/row.受影响条数 data/info.设置后这些优秀作业的数据
	 */
	public function setExcellentWorks($rid) {
		$raw = null;
		if (is_array($rid))
			$raw = Hws_Record::whereIn('id', $rid);
		else
			$raw = Hws_Record::where('id', '=', $rid);

		$row = $n_unzip = 0;
		$select = $raw->select();
		foreach ($select as $value) {
			$ext = substr(basename($value['file_path']), -4);
			if ($ext == '.zip' || $ext == '.rar') {
				// 作业未解压过
				if (empty($value['unpack_path'])) {
					// 解压作业
					$dst = substr(basename($value['file_path']), 0, -4).'/';
					if ($ext == '.zip')
						$this->unZip($value['file_path'], self::HW_UNZIP_FOLDER.$dst);
					else
						$this->unRar($value['file_path'], self::HW_UNZIP_FOLDER.$dst);
				} else {
					$dst = $value['unpack_path'];
				}

				if (file_exists(self::HW_UNZIP_FOLDER.$dst) || $dst === $value['unpack_path']) {
					Hws_Record::where('id', '=', $value['id'])->update([
						'recommend' => 1,
						'unpack_path' => $dst
					]);
					$n_unzip++;
				} else {
					// 解压失败
					//Hws_Record::where('id', '=', $value['id'])->update(['recommend' => 1]);
				}
			} else {
				Hws_Record::where('id', '=', $value['id'])->update([
					'recommend' => 1
				]);
				$n_unzip++;
			}
			$row++;
		}

		if ($row == $n_unzip)
			Response::out(200, ['row' => $row, 'info' => $raw->select()]);
		else
			Response::out(507);
	}

	/**作业系统 - 修改作业信息
	 * 
	 * @param int $rid 1 作业ID
	 * @param array $update 1 修改信息
	 * @return status.状态码 data/row.受影响条数
	 */
	public function updateWork($rid, $update) {
		Response::out(200, ['row' => Hws_Record::where('id', '=', $rid)->update($update)]);
	}

	/**作业系统 - 删除作业
	 * 
	 * @param int $rid 1 作业ID
	 * @return status.状态码 data/row.受影响条数
	 */
	public function deleteWork($rid) {
		Response::out(200, ['row' => Hws_Record::where('id', '=', $rid)->delete()]);
	}

	/**作业系统 - 添加任务
	 * 
	 * @param string $title 1 任务标题
	 * @param string $content 1 任务内容
	 * @param enum $department 1 归属部门{'backend','frontend','design','secret'}
	 * @param timestamp $end_time 1 截止时间（13位时间戳）
	 * @param timestamp $start_time 0 起始时间（13位时间戳）
	 * @return status.状态码 errmsg.错误信息 data/tid.新增任务ID
	 */
	public function addTask($title, $content, $department, $end_time, $start_time = null) {
		$end_time = $this->getJSTimestamp($end_time);	// 转换时间戳
		$start_time = is_null($start_time) ? time() : $this->getJSTimestamp($start_time);
		if ($end_time < time() || $end_time < $start_time) {
			// 时间设置不符
			Response::out(505);
			return;
		}

		$tid = Hws_Task::insert([
			'title' => $title,
			'content' => $content,
			'department' => $department,
			'start_time' => date("Y-m-d H:i:s", $start_time),
			'end_time' => date("Y-m-d H:i:s", $end_time)
		]);
		Response::out(200, ['tid' => $tid[0]]);
	}

	/**作业系统 - 修改任务
	 * 
	 * @param int $tid 1 任务ID
	 * @param array $task_update 1 修改信息
	 * @return status.状态码 data/row.受影响条数
	 */
	public function updateTask($tid, $task_update) {
		Response::out(200, ['row' => Hws_Task::where('id', '=', $tid)->update($update)]);
	}

	/**作业系统 - 删除任务（会删除对应任务的所有作业）
	 * 
	 * @param int $tid 1 任务ID
	 * @return status.状态码 data/task_row.任务受影响条数 data/work_row.作业受影响条数
	 */
	public function deleteTask($tid) {
		// 删除对应任务的所有作业
		Response::out(200, ['task_row' => Hws_Task::where('id', '=', $tid)->delete(),
			'work_row' => Hws_Record::where('tid', '=', $tid)->delete()
		]);
		
	}

	/**作业系统 - 手动截止任务
	 * 
	 * @param int $tid 1 任务ID
	 * @return status.状态码 data/row.受影响条数
	 */
	public function setTaskOff($tid) {
		Response::out(200, ['row' => Hws_Task::where('id', '=', $tid)->update([
			'end_time' => date("Y-m-d H:i:s", time())
		])]);
	}

	/*作业系统 - 转换Javascript的时间戳
	 * 
	 * @param timestamp $timestamp 13位时间戳
	 * @return timestamp.PHP时间戳
	 */
	private function getJSTimestamp($timestamp) {
		if (strlen($timestamp) == 13)	// JS的13位时间戳
			return substr($timestamp, 0, -3);
		else 	// 格式化时间
			return strtotime($timestamp);
	}

	/*数组转码
	 * 
	 * @param string $in_charset 源码
	 * @param string $out_charset 目的编码
	 * @param array $data 数据
	 * @return array
	 */
	private function mult_iconv($in_charset, $out_charset, $data) {
		if (substr($out_charset, -8) == '//IGNORE') {
			$out_charset = substr($out_charset, 0, -8);
		}
		if (is_array($data)) {
			foreach ($data as $key => $value) {
				if (is_array($value)) {
					$key = iconv($in_charset, $out_charset.'//IGNORE', $key);
					$rtn[$key] = $this->mult_iconv($in_charset, $out_charset, $value);
				} elseif (is_string($key) || is_string($value)) {
					if (is_string($key)) {
						$key = iconv($in_charset, $out_charset.'//IGNORE', $key);
					}
					if(is_string($value)){
						$value = iconv($in_charset, $out_charset.'//IGNORE', $value);
					}
					$rtn[$key] = $value;
				} else {
					$rtn[$key] = $value;
				}
			}
		} elseif (is_string($data)) {
			$rtn = iconv($in_charset, $out_charset.'//IGNORE', $data);
		} else {
			$rtn = $data;
		}
		return $rtn;
	}

	/*Zip解压
	 * 
	 * @param string $path zip文件路径
	 * @param string $dst 解压路径
	 * @return array/string
	 */
	private function unZip($path, $dst) {
		$zip = new PclZip($path);
		if (($list = $zip->extract(PCLZIP_OPT_PATH, $dst)) == 0) {
			return $zip->errorInfo();
		}
		return $list;
		//$this->mult_iconv('gb2312', 'utf-8', $list);
	}

	/*Rar解压
	 * 
	 * @param string $path rar文件路径
	 * @param string $dst 解压路径
	 * @return array/boolean
	 */
	private function unRar($path, $dst) {
		\RarException::setUsingExceptions(true);
		try {
			$rar = \RarArchive::open($path);
			$list = $rar->getEntries();
		} catch (\RarException $e) {
			return false;
		}
	
		// var_dump($list); 对象		
		$result = [];

		foreach($list as $file) {
			$pattern = '/\".*\"/';    
			preg_match($pattern, $file, $matches, PREG_OFFSET_CAPTURE);
			$pathStr = $matches[0][0];
			$pathStr = str_replace("\"", '', $pathStr);
			array_push($result, $pathStr);
			$entry = $rar->getEntry($pathStr);
			if ($entry)
				$entry->extract($dst); // extract to the current dir
		}
		$rar->close();
		return $result;
	}
}
?>