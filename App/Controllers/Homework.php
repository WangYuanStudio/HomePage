<?php
namespace App\Controllers;

use App\Models\Hws_Record;
use App\Models\Hws_Task;
use App\Lib\Document;
use App\Controllers\Session;
use App\Lib\Response;

/*
 * Homework
 * Copyright @ WangYuanStudio
 *
 * Author: laijingwu
 * Last modified time: 2016-08-21 10:32
 */
class Homework
{
	public $middle = [
		'uploadWork' => 'Hws_WorkSubmit',
		'correctWork' => 'Hws_Management',
		'setExcellentWorks' => 'Hws_Management',
		'updateWork' => 'Hws_Management',
		'deleteWork' => 'Hws_Management',
		'addTask' => 'Hws_Management',
		'updateTask' => 'Hws_Management',
		'deleteTask' => 'Hws_Management',
		'setTaskOff' => 'Hws_Management',
		'all' => 'Check_login' // 对所有方法判断登录
	];

	/*错误代码 && 错误信息
	 * 
	 * @var array
	 */
	public static $status = [
		500 => 'Invalid department.',
		501 => 'Homework data insert failed. Maybe error occurs in mysql operation.',
		502 => 'It is not the time to submit homework.',
		503 => 'Invalid tid.',
		504 => 'Invalid Rid.',
		505 => 'The deadline should be greater than nowtime and start time.'
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
	private function getExcellentWorksRaw($uid = 0) {
		$raw = Hws_Record::where('recommend', '=', 1);
		if ($uid > 0)
			$raw = $raw->andWhere('uid', '=', $uid);
		return $raw->orderBy('time desc')->select();
	}

	/**作业系统 - 获取某用户优秀作业
	 * 
	 * @param int $uid 0 用户ID（默认当前登录用户）
	 * @return status.状态码 data.用户优秀作业
	 */
	public function getExcellentWorksFromUid($uid = null) {
		Response::out(200, $this->getExcellentWorksRaw(is_null($uid) ? $this->loginedUser['uid'] : $uid));
	}

	/**作业系统 - 获取所有优秀作业
	 * 
	 * @return status.状态码 data.优秀作业
	 */
	public function getExcellentWorks() {
		Response::out(200, $this->getExcellentWorksRaw(0));
	}

	/**作业系统 - 获取某任务的所有作业
	 * 
	 * @param int $tid 1 作业ID
	 * @return status.状态码 data.对应任务的所有作业
	 */
	public function getWorksFromTid($tid) {
		Response::out(200, Hws_Record::where('tid', '=', $tid)->select());
	}

	/**作业系统 - 获取某用户的所有作业
	 * 
	 * @param int $uid 0 用户ID（默认当前登录用户）
	 * @return status.状态码 data.对应用户的所有作业
	 */
	public function getWorksFromUid($uid = null) {
		Response::out(200, Hws_Record::where('uid', '=', is_null($uid) ? $this->loginedUser['uid'] : $uid)->select());
	}

	/**作业系统 - 获取单个作业
	 * 
	 * @param int $rid 1 作业ID
	 * @return status.状态码 data.作业相关信息
	 */
	public function getWorksFromRid($rid) {
		Response::out(200, Hws_Record::where('id', '=', $rid)->select());
	}

	/**作业系统 - 获取所有作业
	 * 
	 * @return status.状态码 data.所有作业
	 */
	public function getAllWorks() {
		Response::out(200, Hws_Record::select());
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
					'uid' => 1,
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

	/**作业系统 - 获取所有任务
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
}
?>