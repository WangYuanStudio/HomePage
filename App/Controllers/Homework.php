<?php
namespace App\Controllers;

use App\Models\Hws_Record;
use App\Models\Hws_Task;
use App\Lib\Document;
use App\Controllers\Session;
use App\Lib\Response;
use App\Lib\PclZip;
use App\Lib\Authorization;

/*
 * Homework
 * Copyright @ WangYuanStudio
 *
 * Author: laijingwu
 * Last modified time: 2016-08-24 01:32
 */
class Homework
{
	public $middle = [
		'getExcellentWorksWhere' => 'Hws_SeeWork',
		// 'getExcellentWorks' => 'Hws_SeeWork',
		// 'getWorksFromLogined' => 'Hws_SeeWork',
		'uploadWork' => 'Hws_WorkSubmit',
		'getWorksFromTid' => 'Hws_Management',
		'getAllWorks' => 'Hws_Management',
		'getWorksFromRid' => 'Hws_Management',
		'correctWork' => 'Hws_Management',
		'setExcellentWorks' => 'Hws_Management',
		'updateWork' => 'Hws_Management',
		'deleteWork' => 'Hws_Management',
		'addTask' => 'Hws_Management',
		'updateTask' => 'Hws_Management',
		'deleteTask' => 'Hws_Management',
		'setTaskOff' => 'Hws_Management',
		'all' => ['Check_login', 'Hws_SeeWork'] // 对所有方法判断登录
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
		506 => 'Can\'t not see homeworks.'	// 无权限查看作业
	];

	/*登录用户Session数据
	 * 
	 * @var array
	 */
	private $loginedUser;

	public function __construct() {
		$this->loginedUser = Session::get('user');
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
	 * @return status.状态码 data.用户优秀作业
	 */
	public function getExcellentWorksWhere($uid = null, $tid = null) {
		Response::out(200, $this->getExcellentWorksRaw(
			is_null($uid) ? $this->loginedUser['uid'] : $uid,
			is_null($tid) ? 0 : $tid
		));
	}

	/**作业系统 - 获取所有优秀作业
	 * 
	 * @return status.状态码 data.优秀作业
	 */
	public function getExcellentWorks() {
		Response::out(200, $this->getExcellentWorksRaw(0));
	}

	/**作业系统 - 获取当前登录用户的所有作业
	 * 
	 * @return status.状态码 data.当前登录用户的所有作业
	 */
	public function getWorksFromLogined() {
		Response::out(200, Hws_Record::where('uid', '=', $this->loginedUser['uid'])
			->select('id, tid, uid, time, note, score, comment, comment_uid, comment_time, recommend'));
	}
	
	/**作业系统 - 上传作业
	 *
	 * @param string $form_filename 1 文件上传组件名
	 * @param int $tid 1 任务ID
	 * @param string $path 0 作业路径
	 * @param string $note 0 作业备注
	 * @return status.状态码 errmsg.错误信息 data/rid.作业ID
	 */
	public function uploadWork($form_filename, $tid, $path = 'upload/', $note = null) {
		if ($task = Hws_Task::where('id', '=', $tid)->select()) {
			// 验证提交开放时间
			if (time() >= strtotime($task[0]['start_time']) &&
			time() <= strtotime($task[0]['end_time'])) {
				// 文件上传
				$src = Document::Upload($form_filename, $path);
				if ($rid = Hws_Record::insert([
					'tid' => $tid,
					'uid' => $this->loginedUser['uid'], // 当前登录用户
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
	 * @param string $department 0 部门名称
	 * @return status.状态码 data.所有任务信息
	 */
	public function getAllTasks($department = null) {
		if ($department)
			$raw = Hws_Task::where('department', '=', $department)->select();
		else
			$raw = Hws_Task::select();

		Response::out(200, $raw);
	}

	/* 下方为管理员/正式成员功能 */

	/**作业系统 - 获取某任务的所有作业（以下为作业管理权限可用）
	 * 
	 * @param int $tid 1 作业ID
	 * @return status.状态码 data.对应任务的所有作业
	 */
	public function getWorksFromTid($tid) {
		Response::out(200, Hws_Record::where('tid', '=', $tid)->select());
	}

	/**作业系统 - 获取所属部门的所有作业
	 * 
	 * @return status.状态码 data.所有作业
	 */
	public function getAllWorks() {
		// 获取登录用户对应所有权限
		$permission = Authorization::getExistingPermission($this->loginedUser['role_id']);
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
	 * @return status.状态码 data.作业相关信息
	 */
	public function getWorksFromRid($rid) {
		Response::out(200, Hws_Record::where('id', '=', $rid)->select());
	}

	/**作业系统 - 批改作业
	 * 
	 * @param int $rid 1 作业ID
	 * @param char $score 1 等级/分数
	 * @param string $comment 1 评语
	 * @param bit $recommend 0 是否推荐（1/0）
	 * @return status.状态码 errmsg.错误信息 data/row.受影响条数
	 */
	public function correctWork($rid, $score, $comment, $recommend = 0) {
		// 验证rid
		if (Hws_Record::where('id', '=', $rid)->select()) {
			$row = Hws_Record::where('id', '=', $rid)->update([
				'score' => $score,
				'comment' => $comment,
				'comment_uid' => $this->loginedUser['uid'],
				'comment_time' => date("Y-m-d H:i:s", time()),
				'recommend' => $recommend
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
	 * @return status.状态码 data/row.受影响条数
	 */
	public function setExcellentWorks($rid) {
		$raw = null;
		if (is_array($rid))
			$raw = Hws_Record::whereIn('id', $rid);
		else
			$raw = Hws_Record::where('id', '=', $rid);

		Response::out(200, ['row' => $raw->update(['recommend' => 1])]);
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

	/**作业系统 - 删除任务
	 * 
	 * @param int $tid 1 任务ID
	 * @return status.状态码 data/row.受影响条数
	 */
	public function deleteTask($tid) {
		Response::out(200, ['row' => Hws_Task::where('id', '=', $tid)->delete()]);
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

	public function test() {
		$zip = new PclZip('1111.zip');

		if (($list = $zip->listContent()) == 0) {
			die('error');
		}

		//$list = $zip->extract(PCLZIP_OPT_PATH, 'homework/1111/');  
		var_dump($list);
	}
}
?>