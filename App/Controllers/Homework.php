<?php
namespace App\Controllers;

use App\Models\Hws_Record;
use App\Models\Hws_Task;
use App\Lib\Document;
use App\Controllers\Session;
use App\Lib\Response;
use App\Lib\PclZip;
use App\Lib\Authorization;
use App\Models\User;
use App\Models\Info;
use App\Controllers\Common;

date_default_timezone_set('PRC');

/*
 * Homework
 * Copyright @ WangYuanStudio
 *
 * Author: laijingwu
 * Last modified time: 2016-09-10 00:28
 */
class Homework
{
	const HW_UPLOAD_FOLDER = 'upload/';	// 作业上传存储路径
	const AT_UPLOAD_FOLDER = 'upload/';	// 任务附件上传存储路径
	const HW_UNZIP_FOLDER = __ROOT__.'/homework/';	// 优秀作业解压存储路径
	const HW_ALLOWDUPLOAD_FILEEXT = [	// 作业允许上传的文件扩展名
		'zip', 'rar'
	];
	const HW_ALLOWDVIEW_FILEEXT = [	// 允许预览的文件扩展名
		'php', 'txt', 'md', 'html', 'htm', 'css', 'js', 'aspx', 'asp', 'sql'
	];
	const HW_DEPARTMEMT = ['backend', 'secret', 'frontend', 'design'];	// 部门标识符
	const HW_DEPARTMEMT_ZH = [
		'backend' => '编程部',
		'secret' => '文秘部',
		'frontend' => '页面部前端',
		'design' => '页面部设计'
	];	// 部门对应中文名

	public $middle = [
		// 'uploadWork' => ['Hws_WorkSubmit', 'Check_Operation_Count'],
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
		504 => 'Invalid rid.',	// 无效的作业
		// 截止时间应大于现在和起始时间
		505 => 'The deadline should be greater than nowtime and start time.',
		506 => 'Not allow to see homeworks.',	// 无权限查看作业
		507 => 'Unzip homework failed.',	// 解压作业失败
		508 => 'The homework has not been extracted.',	// 作业还没有被解压
		// 打开文件夹失败，请注意系统是否具有权限
		509 => 'Open dir failed. Please notice the system permission.',
		510 => 'File does not exist.',	// 文件不存在
		511 => 'This file cannot be preview.',	// 文件不支持预览
		512 => 'Not allow to change department into other.',	// 无权转至其他部门
		513 => 'File upload error.',	// 文件上传失败
		514 => 'This file extension is not allow to upload.',	// 该类扩展名文件不允许上传
		515 => 'File should be uploaded.',	// 没有上传文件
		516 => 'Cannot open the file.',	// 文件无法打开
		517 => 'Invalid filename.',	// 无效的文件名
		518 => 'Invalid type.',	// 无效的类型
		519 => 'Invalid score.',	// 不存在该分数等级
		520 => 'Without search result.'	// 无搜索结果
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

	/**作业系统 - 获取某用户/某任务优秀作业
	 * 
	 * @param int $uid 0 用户ID（默认当前登录用户，为0时则查任务的优秀作业）
	 * @param int $tid 0 任务ID（为空时则查用户的所有优秀作业）
	 * @param int $page 0 当前页数（默认为1）
	 * @return status.状态码 totalItem.结果总条数 totalPage.总页数 currentPage.当前页数 perPage.每页条数 pageData/id.作业ID pageData/tid.任务ID pageData/uid.提交用户ID pageData/file_path.作业文件路径 pageData/time.提交时间 pageData/note.备注 pageData/score.评分 pageData/comment.评语 pageData/comment_uid.批改用户ID pageData/comment_time.批改时间 pageData/recommend.是否推荐 pageData/unpack_path.解压后路径 pageData/user.提交用户信息
	 */
	public function getExcellentWorks($uid = 0, $tid = 0, $page = 1) {
		$raw = Hws_Record::where('recommend', '=', 1);
		if ($uid > 0)
			$raw = $raw->andWhere('uid', '=', $uid);
		if ($tid > 0)
			$raw = $raw->andWhere('tid', '=', $tid);
		$data = $raw->orderBy('time desc')->select();
		$perPage = 4;
		$totalItem = count($data);
		$totalPage = (int)(count($data) / $perPage) + ((count($data) % $perPage != 0) ? 1 : 0);
		$pageData = [];
		for ($i = ($page - 1) * $perPage; $i < $page * $perPage && $i < $totalItem; $i++) {
			array_push($pageData, $data[$i]);
		}
		// 查询用户信息
		for ($i = 0; $i < count($pageData); $i++) {
			$pageData[$i]['user'] = [];
			$userInfo = User::leftJoin('info', 'user.id', '=', 'info.uid')
				->where('id', '=', $pageData[$i]['uid'])
				->select('user.nickname, user.mail, user.photo, info.*');
			if ($userInfo) {
				$pageData[$i]['user'] = $userInfo[0];
			}
		}
		Response::out(200, [
			'totalItem' => $totalItem,
			'totalPage' => $totalPage,
			'currentPage' => $page,
			'perPage' => $perPage,
			'pageData' => $pageData
		]);
	}
	
	/**作业系统 - 上传作业
	 * 
	 * @param string $file_field 1 文件上传组件名
	 * @param int $tid 1 任务ID
	 * @param string $note 0 作业备注
	 * @return status.状态码 errmsg.错误信息 rid.作业ID
	 */
	public function uploadWork($file_field, $tid, $note = null) {
		if ($task = Hws_Task::where('id', '=', $tid)->select()) {
			// 验证提交开放时间
			if (time() >= strtotime($task[0]['start_time']) &&
			time() <= strtotime($task[0]['end_time'])) {
				// 文件上传
				if (!isset($_FILES[$file_field])) {	// 没有上传作业
					Response::out(515);
					return;
				}
				// 检查扩展名
				if (!in_array(substr(strrchr($_FILES[$file_field]['name'], '.'), 1), self::HW_ALLOWDUPLOAD_FILEEXT)) {
					// 该文件扩展不允许上传
					Response::out(514);
					return;
				}
				$src = Document::Upload($file_field, self::HW_UPLOAD_FOLDER);
				//$src = substr($src, strlen(self::HW_UPLOAD_FOLDER));
				if (empty($src)) {
					// 文件上传失败
					Response::out(513);
					return;
				}
				if ($rid = Hws_Record::insert([
					'tid' => $tid,
					'uid' => $this->loginedUser['id'], // 当前登录用户
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
	 * @param int $page 0 当前页数（默认为1）
	 * @return status.状态码 totalItem.结果总条数 totalPage.总页数 currentPage.当前页数 perPage.每页条数 pageData/id.任务ID pageData/title.标题 pageData/content.内容 pageData/department.部门 pageData/start_time.提交起始时间 pageData/end_time.提交截止时间
	 */
	public function getAllTasks($department = null, $page = 1) {
		if ($department) {
			if (!in_array($department, self::HW_DEPARTMEMT)) {
				Response::out(500);
				return;
			}
			$data = Hws_Task::where('department', '=', $department)->orderBy("id")->select();
		} else {
			$data = Hws_Task::orderBy("id")->select();
		}
		$perPage = 4;
		$totalItem = count($data);
		$totalPage = (int)(count($data) / $perPage) + ((count($data) % $perPage != 0) ? 1 : 0);
		$pageData = [];
		for ($i = ($page - 1) * $perPage; $i < $page * $perPage && $i < $totalItem; $i++) {
			array_push($pageData, $data[$i]);
		}
		Response::out(200, [
			'totalItem' => $totalItem,
			'totalPage' => $totalPage,
			'currentPage' => $page,
			'perPage' => $perPage,
			'pageData' => $pageData
		]);
	}

	/**作业系统 - 获取解压后优秀作业目录
	 * 
	 * @param int $rid 1 作业ID
	 * @return status.状态码 dir.目录 file.文件
	 */
	public function getUnzipWorkDir($rid) {
		$w = Hws_Record::whereAndWhere(['id', '=', $rid], ['recommend', '=', 1])-> select();
		if ($w) {
			if ($dir = $this->read_all_dir(self::HW_UNZIP_FOLDER.$w[0]['unpack_path'])) {
				Response::out(200, $dir);
			} else {
				Response::out(509);
			}
		} else {
			Response::out(508);
		}
	}

	/**作业系统 - 获取优秀作业文件内容
	 * 
	 * @param string $path 1 文件路径
	 * @return status.状态码 bytes.文件大小（单位字节） lines.文件总行数 modify_time.上次修改时间 text.文件内容
	 */
	public function getUnzipWorkFile($path) {
		// 非法字符检测
		$invalidArr = str_split(':*?"<>|');
		foreach ($invalidArr as $invalid) {
			if (strstr($path, '../') || strstr($path, $invalid)) {
				Response::out(517);
				return;
			}
		}

		// Windows下要转换 中文目录名/中文文件名
		// $path = iconv('utf-8', 'gb2312', $path);
		$path = self::HW_UNZIP_FOLDER.$path;
		if (is_file($path)) {
			$arr = explode('.', $path);
			$ext = end($arr);
			if (in_array($ext, self::HW_ALLOWDVIEW_FILEEXT)) {
				// 打开文件
				$handle = @fopen($path, 'r');
				if (!$handle) {
					// 无法打开文件
					Response::out(516);
					return;
				}
				$contents = stream_get_contents($handle);
				fclose($handle);
				$contents = mb_convert_encoding($contents, 'UTF-8','UTF-8, GB2312, GBK, ASCII');
				Response::out(200, [
					'bytes' => filesize($path),
					'lines' => count(file($path)),
					'modify_time' => date("Y-m-d H:i:s", filemtime($path)),
					'text' => $contents
				]);
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

	/**作业系统 - 获取当前任务中自己所提交的所有作业/管理-获取某任务的所有作业
	 * 
	 * @param int $tid 1 作业ID
	 * @param int $page 0 当前页数（默认为1）
	 * @return status.状态码 errmsg.错误信息 totalItem.结果总条数 totalPage.总页数 currentPage.当前页数 perPage.每页条数 pageData/id.作业ID pageData/tid.任务ID pageData/uid.提交用户ID pageData/file_path.作业文件路径 pageData/time.提交时间 pageData/note.备注 pageData/score.评分 pageData/comment.评语 pageData/comment_uid.批改用户ID pageData/comment_time.批改时间 pageData/recommend.是否推荐 pageData/unpack_path.解压后路径 pageData/user.提交用户信息
	 */
	public function getWorksFromTid($tid, $page = 1) {
		$permission = Authorization::getExistingPermission($this->loginedUser['role']);
		$department_permission = [];
		$data = [];
		foreach ($permission as $value) {
			if (substr($value['name'], 0, strlen('manage_')) == 'manage_' && substr($value['name'], 0-strlen('_homeworks')) == '_homeworks') {
				// 满足管理权限
				$department = substr($value['name'], strlen('manage_'), 0-strlen('_homeworks'));
				array_push($department_permission, $department);
			}
		}
		if (empty($department_permission)) {
			// 实习生 获取当前任务中自己所提交的所有作业
			$data = Hws_Record::whereAndWhere(['tid', '=', $tid], ['uid', '=', $this->loginedUser['id']])->select('id, tid, uid, time, note, score, comment, comment_time, recommend');
		} else {
			// 管理权限 获取当前任务中所有作业
			if (($t = Hws_Task::where('id', '=', $tid)->select()) && 
				in_array($t[0]['department'], $department_permission)) {
				// 拥有权限
				$data = Hws_Record::where('tid', '=', $tid)->select();
			} else {
				// 无权限
				Response::out(301);
				return;
			}
		}
		$perPage = 4;
		$totalItem = count($data);
		$totalPage = (int)(count($data) / $perPage) + ((count($data) % $perPage != 0) ? 1 : 0);
		$pageData = [];
		for ($i = ($page - 1) * $perPage; $i < $page * $perPage && $i < $totalItem; $i++) {
			array_push($pageData, $data[$i]);
		}
		// 查询用户信息
		for ($i = 0; $i < count($pageData); $i++) {
			$pageData[$i]['user'] = [];
			$userInfo = User::leftJoin('info', 'user.id', '=', 'info.uid')
				->where('id', '=', $pageData[$i]['uid'])
				->select('user.nickname, user.mail, user.photo, info.*');
			if ($userInfo) {
				$pageData[$i]['user'] = $userInfo[0];
			}
		}
		Response::out(200, [
			'totalItem' => $totalItem,
			'totalPage' => $totalPage,
			'currentPage' => $page,
			'perPage' => $perPage,
			'pageData' => $pageData
		]);
	}

	/**作业系统 - 管理 - 获取所属部门的作业（全部/已批改/未批改）
	 *
	 * @param int $type 0 类型（0:全部(默认)，1:已批改，2:未批改）
	 * @param int $page 0 当前页数（默认为1）
	 * @return status.状态码 errmsg.错误信息 totalItem.结果总条数 totalPage.总页数 currentPage.当前页数 perPage.每页条数 pageData/id.作业ID pageData/tid.任务ID pageData/uid.提交用户ID pageData/file_path.作业文件路径 pageData/time.提交时间 pageData/note.备注 pageData/score.评分 pageData/comment.评语 pageData/comment_uid.批改用户ID pageData/comment_time.批改时间 pageData/recommend.是否推荐 pageData/unpack_path.解压后路径 pageData/user.提交用户信息
	 */
	public function getAllWorks($type = 0, $page = 1) {
		// 获取登录用户对应所有权限
		$permission = Authorization::getExistingPermission($this->loginedUser['role']);
		$department_array = [];

		// 获取角色具有哪个部门的管理权限
		foreach ($permission as $value) {
			$value = $value['name'];
			if (substr($value, 0, strlen('manage_')) == 'manage_' && 
				substr($value, 0 - strlen('_homeworks')) == '_homeworks') {
				$tmp = substr($value, strlen('manage_'), 0 - strlen('_homeworks'));
				array_push($department_array, $tmp);
			}
		}

		// 防止实习生获取作业
		if (empty($department_array)) {
			Response::out(301);
			return;
		}

		// 所属部门任务对应的任务ID
		$t = Hws_Task::where('department', '=', $department_array[0]);
		for ($i = 1; $i < count($department_array); $i++) {
			$t = $t->orWhere('department', '=', $department_array[$i]);
		}
		$t = $t->select('id');
		$whereid = [];
		foreach ($t as $value) {
			array_push($whereid, $value['id']);
		}
		// 任务ID对应的作业
		$w = Hws_Record::whereIn('tid', $whereid)->select();
		$data = [];
		if ($type) {
			foreach ($w as $value) {
				if ($type == 2 && is_null($value['score']))	// 未批改
					array_push($data, $value);
				else if ($type == 1 && !is_null($value['score']))	// 已批改
					array_push($data, $value);
			}
		} else {
			$data = $w;
		}

		$perPage = 4;
		$totalItem = count($data);
		$totalPage = (int)(count($data) / $perPage) + ((count($data) % $perPage != 0) ? 1 : 0);
		$pageData = [];
		for ($i = ($page - 1) * $perPage; $i < $page * $perPage && $i < $totalItem; $i++) {
			array_push($pageData, $data[$i]);
		}
		// 查询用户信息
		for ($i = 0; $i < count($pageData); $i++) {
			$pageData[$i]['user'] = [];
			$userInfo = User::leftJoin('info', 'user.id', '=', 'info.uid')
				->where('id', '=', $pageData[$i]['uid'])
				->select('user.nickname, user.mail, user.photo, info.*');
			if ($userInfo) {
				$pageData[$i]['user'] = $userInfo[0];
			}
		}
		Response::out(200, [
			'totalItem' => $totalItem,
			'totalPage' => $totalPage,
			'currentPage' => $page,
			'perPage' => $perPage,
			'pageData' => $pageData
		]);
	}

	/**作业系统 - 管理 - 批改作业/修改批语
	 * 
	 * @param int $rid 1 作业ID
	 * @param char $score 1 等级/分数
	 * @param string $comment 1 评语
	 * @return status.状态码 errmsg.错误信息
	 */
	public function correctWork($rid, $score, $comment) {
		// 验证rid
		if ($t = Hws_Record::hasMany("getTask")->where('hws_record.id', '=', $rid)->select()) {
			// 权限判断
			if (!Authorization::isAuthorized($this->loginedUser['role'], 'manage_'.$t[0]['department'].'_homeworks')) {
				Response::out(301);
				return;
			}
			if (!in_array($score, ['A', 'B', 'C', 'D'])) {
				Response::out(519);
				return;
			}
			// 已批改
			if (!is_null($t[0]['score'])) {
				//Response::out(516);
				//return;
			}
			$row = Hws_Record::where('id', '=', $rid)->update([
				'score' => $score,
				'comment' => $comment,
				'comment_uid' => $this->loginedUser['id'],
				'comment_time' => date("Y-m-d H:i:s", time())
			]);
			// 消息通知
			Common::setInform($t[0]['uid'], "作业消息", "作业已被批改", "您于".$t[0]['time']."提交的作业已被批改，快去看看！", "");
			Response::out(200);
		} else {
			// rid无效
			Response::out(504);
		}
	}

	/**作业系统 - 管理 - 设置优秀作业
	 * 
	 * @param int $rid 1 作业ID
	 * @return status.状态码 errmsg.错误信息
	 */
	public function setExcellentWorks($rid) {
		$raw = Hws_Record::where('id', '=', $rid)->select();
		if (empty($raw)) {
			// 不存在该作业
			Response::out(504);
			return;
		}
		$t = Hws_Record::hasMany("getTask")->where('hws_record.id', '=', $rid)->select();
		// 权限判断
		if (!Authorization::isAuthorized($this->loginedUser['role'], 'manage_'.$t[0]['department'].'_homeworks')) {
			Response::out(301);
			return;
		}
		$value = $raw[0];
		$ext = substr(basename($value['file_path']), -4);
		// 解压仅限于编程和前端
		if ($t && 
			($t[0]['department'] == 'backend' || $t[0]['department'] == 'frontend') && 
			($ext == '.zip' || $ext == '.rar') && 
		is_null($value['unpack_path'])) {
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
				// 消息通知
				Common::setInform($value['uid'], "作业消息", "作业被设置为优秀作业", "您于".$value['time']."提交的作业已被设置为优秀作业，快去看看！", "");
				Response::out(200);
			} else {
				// 解压失败
				//Hws_Record::where('id', '=', $value['id'])->update(['recommend' => 1]);
				Response::out(507);
			}
		} else {
			if ($t[0]['recommend'] == 0) {
				Hws_Record::where('id', '=', $value['id'])->update([
					'recommend' => 1
				]);
				// 消息通知
				Common::setInform($value['uid'], "作业消息", "作业被设置为优秀作业", "您于".$value['time']."提交的作业已被设置为优秀作业，快去看看！", "");
			}
			Response::out(200);
		}
	}

	/**作业系统 - 管理 - 取消设置优秀作业
	 * 
	 * @param int $rid 作业ID
	 * @return status.状态码 errmsg.错误信息
	 */
	public function cancelExcellentWorks($rid) {
		$raw = Hws_Record::where('id', '=', $rid)->select();
		if (empty($raw)) {
			// 不存在该作业
			Response::out(504);
			return;
		}
		$t = Hws_Record::hasMany("getTask")->where('hws_record.id', '=', $rid)->select();
		// 权限判断
		if (!Authorization::isAuthorized($this->loginedUser['role'], 'manage_'.$t[0]['department'].'_homeworks')) {
			Response::out(301);
			return;
		}

		if ($raw[0]['unpack_path']) {
			// 删除解压文件
			$this->delDirAndFile(self::HW_UNZIP_FOLDER.$raw[0]['unpack_path']);
		}

		Hws_Record::where('id', '=', $raw[0]['id'])->update([
			'recommend' => 0,
			'unpack_path' => null
		]);
		Response::out(200);
	}

	/**作业系统 - 管理 - 删除作业
	 * 
	 * @param int $rid 1 作业ID
	 * @return status.状态码 errmsg.错误信息
	 */
	public function deleteWork($rid) {
		$raw = Hws_Record::where('id', '=', $rid)->select('file_path, unpack_path');
		if (empty($raw)) {
			// 不存在该作业
			Response::out(504);
			return;
		}
		$t = Hws_Record::hasMany("getTask")->where('hws_record.id', '=', $rid)->select();
		// 权限判断
		if (!Authorization::isAuthorized($this->loginedUser['role'], 'manage_'.$t[0]['department'].'_homeworks')) {
			Response::out(301);
			return;
		}

		// 删除作业文件
		if ($raw[0]['file_path']) {
			// 删除压缩包
			@unlink($raw[0]['file_path']);
		}
		if ($raw[0]['unpack_path']) {
			// 删除解压文件
			$this->delDirAndFile(self::HW_UNZIP_FOLDER.$raw[0]['unpack_path']);
		}
		Hws_Record::where('id', '=', $rid)->delete();
		Response::out(200);
	}

	/**作业系统 - 管理 - 添加任务
	 * 
	 * @param string $title 1 任务标题
	 * @param string $content 1 任务内容
	 * @param enum $department 1 归属部门{'backend','frontend','design','secret'}
	 * @param timestamp $end_time 1 截止时间（13位时间戳）
	 * @param timestamp $start_time 0 起始时间（13位时间戳）
	 * @param string $attachments_field 0 附件上传字段名（需要上传时才传）
	 * @param file $(附件字段名) 0 文件
	 * @return status.状态码 errmsg.错误信息 tid.新增任务ID
	 */
	public function addTask($title, $content, $department, $end_time, $start_time = null, $attachments_field = null) {
		if (!in_array($department, self::HW_DEPARTMEMT)) {
			Response::out(500);
			return;
		}
		if (!Authorization::isAuthorized($this->loginedUser['role'], 'manage_'.$department.'_homeworks')) {
			// 无管理权限
			Response::out(301);
			return;
		}

		$end_time = $this->getJSTimestamp($end_time);	// 转换时间戳
		$start_time = is_null($start_time) ? time() : $this->getJSTimestamp($start_time);
		if ($end_time < time() || $end_time < $start_time) {
			// 时间设置不符
			Response::out(505);
			return;
		}

		// 上传附件
		$src = null;
		if ($attachments_field) {
			$path = self::AT_UPLOAD_FOLDER;
			$src = Document::Upload($attachments_field, $path);
			if (empty($src)) {
				// 附件上传失败
				Response::out(513);
				return;
			}
		}

		$tid = Hws_Task::insert([
			'title' => $title,
			'content' => $content,
			'department' => $department,
			'start_time' => date("Y-m-d H:i:s", $start_time),
			'end_time' => date("Y-m-d H:i:s", $end_time),
			'attachments' => $src
		]);
		Response::out(200, ['tid' => $tid[0]]);
	}

	/**作业系统 - 管理 - 修改任务
	 * 
	 * @param int $tid 1 任务ID
	 * @param string $title 0 任务标题
	 * @param string $content 0 任务内容
	 * @param enum $department 0 归属部门{'backend','frontend','design','secret'}
	 * @param timestamp $end_time 0 截止时间（13位时间戳）
	 * @param timestamp $start_time 0 起始时间（13位时间戳）
	 * @param string $attachments_field 0 附件上传字段名
	 * @param file $(附件字段名) 0 文件
	 * @return status.状态码 errmsg.错误信息 row.受影响条数
	 */
	public function updateTask($tid, $title = null, $content = null, $department = null, $end_time = null, $start_time = null, $attachments_field = null) {
		if ($t = Hws_Task::where('id', '=', $tid)->select()) {
            if (!Authorization::isAuthorized($this->loginedUser['role'], 'submit_'.$t[0]['department'].'_homeworks')) {
            	// 无管理权限
            	Response::out(301);
                return;
            }
        } else {
        	// 不存在TID
        	Response::out(503);
        	return;
        }
		$task_update = [];
		if ($title) $task_update['title'] = $title;
		if ($content) $task_update['content'] = $content;
		if ($end_time) $task_update['end_time'] = $end_time;
		if ($start_time) $task_update['start_time'] = $start_time;
		if ($department) {
			if (!Authorization::isAuthorized(
				$this->loginedUser['role'],
				'manage_'.$department.'_homeworks'
			)) {
				// 无权转至其他部门
				Response::out(512);
				return;
			}
			$task_update['department'] = $department;
		}

		// 上传附件
		$src = null;
		if ($attachments_field) {
			$path = self::AT_UPLOAD_FOLDER;
			$src = Document::Upload($attachments_field, $path);
			if (empty($src)) {
				// 附件上传失败
				Response::out(513);
				return;
			}
			// 删除旧的附件
			if ($tmp = Hws_Task::where('id', '=', $tid)->select()) {
				@unlink($tmp[0]['attachments']);
			}
		}
		Response::out(200, ['row' => Hws_Task::where('id', '=', $tid)->update($task_update)]);
	}

	/**作业系统 - 管理 - 删除任务（会删除对应任务的所有作业）
	 * 
	 * @param int $tid 1 任务ID
	 * @return status.状态码 errmsg.错误信息
	 */
	public function deleteTask($tid) {
		$task_arr = Hws_Task::where('id', '=', $tid)->select();
		if ($task_arr) {
            if (!Authorization::isAuthorized($this->loginedUser['role'], 'submit_'.$task_arr[0]['department'].'_homeworks')) {
            	// 无管理权限
            	Response::out(301);
                return;
            }
        } else {
        	// 不存在TID
        	Response::out(503);
        	return;
        }
		// 删除附件
		if (!empty($task_arr[0]['attachments'])) {
			@unlink($task_arr[0]['attachments']);
		}
		$work_arr = Hws_Record::where('tid', '=', $tid)->select();
		if ($work_arr) {
			foreach ($work_arr as $value) {
				if ($value['file_path']) {
					// 删除压缩包
					@unlink($value['file_path']);
				}
				if ($value['unpack_path']) {
					// 删除解压文件
					$this->delDirAndFile(self::HW_UNZIP_FOLDER.$value['unpack_path']);
				}
			}
			// 删除对应任务的所有作业 数据库信息
			Hws_Task::where('id', '=', $tid)->delete();
			Hws_Record::where('tid', '=', $tid)->delete();
		}
		Response::out(200);
	}

	/*循环删除目录和文件
	 * 
	 * @param string $dirName 路径
	 * @return void
	 */
	private function delDirAndFile($dirName) {
		if (substr($dirName, -1) == '/')
			$dirName = substr($dirName, 0, -1);

		if ($handle = opendir($dirName)) {
			while (false !== ($item = readdir($handle))) {
				if ($item != "." && $item != "..") {
					if (is_dir($dirName.'/'.$item)) {
						$this->delDirAndFile($dirName.'/'.$item);
					} else {
						@unlink($dirName.'/'.$item);
					}
				}
			}
			closedir($handle);
			@rmdir($dirName);
		}
	}

	/**作业系统 - 管理 - 手动截止任务
	 * 
	 * @param int $tid 1 任务ID
	 * @return status.状态码 errmsg.错误信息
	 */
	public function setTaskOff($tid) {
		if ($t = Hws_Task::where('id', '=', $tid)->select()) {
            if (!Authorization::isAuthorized($this->loginedUser['role'], 'submit_'.$t[0]['department'].'_homeworks')) {
            	// 无管理权限
            	Response::out(301);
                return;
            }
        } else {
        	// 不存在TID
        	Response::out(503);
        	return;
        }
		Hws_Task::where('id', '=', $tid)->update(['end_time' => date("Y-m-d H:i:s", time())]);
		Response::out(200);
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

	/**作业系统 - 管理 - 导出新生信息/实习生作业成绩
	 * 
	 * @param int $type 1 导出类型{1:新生信息,2:实习生作业成绩}
	 * @return status.状态码 errmsg.错误信息 data.Excel二进制数据（如果成功没有status，直接返回二进制数据，引导页面下载）
	 */
	public function exportData($type) {
		if ($this->loginedUser['role'] != 1) {	// TODO: 管理员角色ID
            // 无管理权限
            Response::out(301);
            return;
        }

		if ($type != 1 && $type != 2) {
			// 无效的type
			Response::out(518);
		}

		if ($type == 1) {


			// 导出新生信息
			

			// 获取新生用户数据
			$newlyInfo = Info::leftJoin('user', 'info.uid', '=', 'user.id')
				->where('privilege', '=', 1)
				->orderBy('department', 'sid', 'grade')
				->select();

			$objReader = new \PHPExcel_Reader_Excel2007();	// 读取模板
			$templateInfo = $objReader->load("../vendor/template_info.xlsx");
			// $resultPHPExcel = new PHPExcel(); 

			// 设值
			for ($i = 0; $i < count($newlyInfo); $i++) {
				$n = $i + 3;	// TODO: 第一行输出行行数
				$templateInfo->getActiveSheet()->setCellValue('A'.$n, $newlyInfo[$i]['sid']);
				$templateInfo->getActiveSheet()->setCellValue('B'.$n, $newlyInfo[$i]['name']);
				$templateInfo->getActiveSheet()->setCellValue('C'.$n, $newlyInfo[$i]['sex']);
				$templateInfo->getActiveSheet()->setCellValue('D'.$n, $newlyInfo[$i]['mail']);
				$templateInfo->getActiveSheet()->setCellValue('E'.$n, self::HW_DEPARTMEMT_ZH[$newlyInfo[$i]['department']]);
				$templateInfo->getActiveSheet()->setCellValue('F'.$n, $newlyInfo[$i]['grade']);
				$templateInfo->getActiveSheet()->setCellValue('G'.$n, $newlyInfo[$i]['college']);
				$templateInfo->getActiveSheet()->setCellValue('H'.$n, $newlyInfo[$i]['major']);
				$templateInfo->getActiveSheet()->setCellValue('I'.$n, $newlyInfo[$i]['phone']);
				$templateInfo->getActiveSheet()->setCellValue('J'.$n, $newlyInfo[$i]['short_phone']);
			}
			$templateInfo->getActiveSheet()->setTitle('新生信息导出表');
			$fileName = 'newlyinfo_'.date("YmdHis", time()).'.xlsx';

		} else if ($type == 2) {


			// 导出实习生作业成绩


			// 获取所有任务信息（注意排序）
			$taskInfo = Hws_Task::orderBy('department, id')->select('id, title, department');

			// 初始化分部门任务空数组
			$taskInfoFromDepartment = [];
			foreach (self::HW_DEPARTMEMT as $val) {
				$taskInfoFromDepartment[$val] = [];
			}

			// 对任务进行分部门
			foreach ($taskInfo as $val) {
				$taskInfoFromDepartment[$val['department']][$val['id']] = $val['title'];
			}

			// 获取实习生用户数据
			$traineeInfo = Info::leftJoin('user', 'info.uid', '=', 'user.id')
				->where('privilege', '=', 1)
				->orderBy('department', 'sid', 'grade')
				->select('uid, sid, name, sex, department');
			
			
			foreach ($traineeInfo as $key => $trainee) {
				// 初始化实习生空作业成绩数组
				foreach ($taskInfoFromDepartment[$traineeInfo[$key]['department']] as $id => $title) {
					$traineeInfo[$key]['homeworks'][$id] = null;
				}

				// 查询用户对应作业
				$recordInfo = Hws_Record::leftJoin('hws_task', 'hws_record.tid', '=', 'hws_task.id')
					->whereNotNull('hws_record.score')
					->_and()
					->whereAndWhere(['hws_record.uid', '=', $trainee['uid']], ['hws_task.department', '=', $traineeInfo[$key]['department']])
					->orderBy('hws_task.department, hws_task.id, hws_record.id desc')
					->select('hws_record.id, hws_record.tid, hws_record.uid, hws_record.score');

				// 覆盖数组中实习生的空作业成绩
				foreach ($recordInfo as $val) {
					if (is_null($traineeInfo[$key]['homeworks'][$val['tid']]))
						$traineeInfo[$key]['homeworks'][$val['tid']] = $val['score'];
				}
			}

			// Excel处理导出
			$objReader = new \PHPExcel_Reader_Excel2007();	// 读取模板
			$templateInfo = $objReader->load("../vendor/template_score.xlsx");

			$sheetTitleArr = [
				'编程部实习生作业成绩导出',
				'文秘部实习生作业成绩导出',
				'页面部前端实习生作业成绩导出',
				'页面部设计实习生作业成绩导出'
			];
			$scoreExchangeInt = [
				'A' => 100,
				'B' => 80,
				'C' => 60,
				'D' => 50
			];

			foreach ($sheetTitleArr as $index => $sheetTitle) {
				// 切换表
				$templateInfo->setActiveSheetIndex($index);
				// 设置单元格中标题
				$templateInfo->getActiveSheet()->setCellValue('A1', $sheetTitle);

				// 任务标题导出
				$n = 0;
				foreach ($taskInfoFromDepartment[self::HW_DEPARTMEMT[$index]] as $id => $title) {
					$first = ord('F') + $n;
					$showFirst = chr($first);
					if ($first > 90) {
						$showFirst = chr(ord('A')+(($first-65)%26)-1).$showFirst;
					}
					$templateInfo->getActiveSheet()->setCellValue($showFirst.'2', $title);
					$templateInfo->getActiveSheet()->getColumnDimension($showFirst)->setWidth(20);
					$n++;
				}

				// 设值
				$n = 3;
				for ($i = 0; $i < count($traineeInfo); $i++) {
					if ($traineeInfo[$i]['department'] != self::HW_DEPARTMEMT[$index])
						continue;

					// 基本信息写入
					$templateInfo->getActiveSheet()->setCellValue('A'.$n, $traineeInfo[$i]['sid']);
					$templateInfo->getActiveSheet()->setCellValue('B'.$n, $traineeInfo[$i]['name']);
					$templateInfo->getActiveSheet()->setCellValue('C'.$n, $traineeInfo[$i]['sex']);
					$templateInfo->getActiveSheet()->setCellValue('D'.$n, self::HW_DEPARTMEMT_ZH[$traineeInfo[$i]['department']]);

					// 成绩写入
					$j = 0;
					$totalScorePer = 0;
					foreach ($taskInfoFromDepartment[self::HW_DEPARTMEMT[$index]] as $id => $title) {
						$first = ord('F') + $j;
						$showFirst = chr($first);
						if ($first > 90) {
							$showFirst = chr(ord('A')+(($first-65)%26)-1).$showFirst;
						}
						$templateInfo->getActiveSheet()->setCellValue($showFirst.$n, $traineeInfo[$i]['homeworks'][$id]);
						$totalScorePer += 
							isset($scoreExchangeInt[$traineeInfo[$i]['homeworks'][$id]])
							 ? $scoreExchangeInt[$traineeInfo[$i]['homeworks'][$id]]
							 : 0;
						$j++;
					}
					$templateInfo->getActiveSheet()->setCellValue('E'.$n, $totalScorePer);
					$n++;
				}
			}
			$fileName = 'score_'.date("YmdHis", time()).'.xlsx';
		}
		$templateInfo->setActiveSheetIndex(0);
		// $objWriter->save('test.xlsx');
		header('Pragma: public');
		header('Expires: 0');
		header('Cache-Control: max-age=0');
		header('Content-Type: application/force-download');
		header('Content-Type: application/vnd.ms-execl');
		header('Content-Type: application/octet-stream');
		header('Content-Type: application/download');
		header('Content-Disposition: attachment;filename="'.$fileName.'"');
		header('Content-Transfer-Encoding: binary');
		$objWriter = \PHPExcel_IOFactory::createWriter($templateInfo, 'Excel2007');
		$objWriter->save('php://output');	// Warning: 可能出现缓冲区不足		
	}

	/**作业系统 - 管理 - 任务搜索
	 * 
	 * @param string $title 1 任务标题关键词
	 * @param int $page 0 当前页数（默认为1）
	 * @return status.状态码 errmsg.错误信息 totalItem.结果总条数 totalPage.总页数 currentPage.当前页数 perPage.每页条数 pageData/id.作业ID pageData/tid.任务ID pageData/uid.提交用户ID pageData/file_path.作业文件路径 pageData/time.提交时间 pageData/note.备注 pageData/score.评分 pageData/comment.评语 pageData/comment_uid.批改用户ID pageData/comment_time.批改时间 pageData/recommend.是否推荐 pageData/unpack_path.解压后路径 pageData/user.提交用户信息
	 */
	public function searchTask($title, $page = 1) {
		$data = Hws_Record::leftJoin('hws_task', 'hws_record.tid', '=', 'hws_task.id')
			->where('hws_task.title', 'like', "%".$title."%")
			->select('hws_task.id, hws_task.title, hws_task.content, hws_task.department, hws_record.*');

		if ($data) {
            if (!Authorization::isAuthorized($this->loginedUser['role'], 'submit_'.$data[0]['department'].'_homeworks')) {
            	// 无管理权限
            	Response::out(301);
                return;
            }
        } else {
        	// 无搜索结果
        	Response::out(520);
        	return;
        }

        $perPage = 4;
		$totalItem = count($data);
		$totalPage = (int)(count($data) / $perPage) + ((count($data) % $perPage != 0) ? 1 : 0);
		$pageData = [];
		for ($i = ($page - 1) * $perPage; $i < $page * $perPage && $i < $totalItem; $i++) {
			array_push($pageData, $data[$i]);
		}
		// 查询用户信息
		for ($i = 0; $i < count($pageData); $i++) {
			$pageData[$i]['user'] = [];
			$userInfo = User::leftJoin('info', 'user.id', '=', 'info.uid')
				->where('id', '=', $pageData[$i]['uid'])
				->select('user.nickname, user.mail, user.photo, info.*');
			if ($userInfo) {
				$pageData[$i]['user'] = $userInfo[0];
			}
		}
		Response::out(200, [
			'totalItem' => $totalItem,
			'totalPage' => $totalPage,
			'currentPage' => $page,
			'perPage' => $perPage,
			'pageData' => $pageData
		]);
	}
}
?>