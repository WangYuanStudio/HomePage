<?php
namespace App\Controllers;

use App\Models\Hws_Record;
use App\Models\Hws_Task;
use App\Lib\Document;

/*
 * Homework
 * Copyright @ WangYuanStudio
 *
 * Author: laijingwu
 * Last modified time: 2016-08-11 11:42
 */
class Homework
{
	//public $middle = "authorization";

	/*作业系统 - 获取优秀作业
	 * 
	 * @param int $uid 用户ID
	 * @return array.优秀作业
	 */
	private function getExcellentWorksRaw($uid = 0) {
		$raw = Hws_Record::where('recommend', '=', 1);
		if ($uid > 0)
			$raw = $raw->andWhere('uid', '=', $uid);
		$raw = $raw->orderBy('time desc')->select();
		return ['count' => count($raw), 'data' => $raw];
	}

	/**作业系统 - 获取某用户优秀作业
	 * 
	 * @param int $uid 1 用户ID
	 * @return count.条目数 data.用户优秀作业数组
	 */
	public function getExcellentWorksFromUid($uid) {
		response($this->getExcellentWorksRaw($uid)));
	}

	/**作业系统 - 获取所有优秀作业
	 * 
	 * @return count.条目数 data.优秀作业数组
	 */
	public function getExcellentWorks() {
		response($this->getExcellentWorksRaw(0));
	}

	/**作业系统 - 获取某任务的所有作业
	 * 
	 * @param int $tid 1 作业ID
	 * @return count.条目数 data.作业数组
	 */
	public function getWorksFromTid($tid) {
		$raw = Hws_Record::where('tid', '=', $tid)->select();
		response(['count' => count($raw), 'data' => $raw]);
	}

	/**作业系统 - 获取某用户的所有作业
	 * 
	 * @param int $uid 1 用户ID
	 * @return count.条目数 data.用户作业数组
	 */
	public function getWorksFromUid($uid) {
		$raw = Hws_Record::where('uid', '=', $uid)->select();
		response(['count' => count($raw), 'data' => $raw]);
	}

	/**作业系统 - 获取单个作业
	 * 
	 * @param int $rid 1 作业ID
	 * @return data.作业相关信息
	 */
	public function getWorksFromRid($rid) {
		$raw = Hws_Record::where('id', '=', $rid)->select();
		response(['data' => $raw]);
	}

	/**作业系统 - 获取所有作业
	 * 
	 * @return count.条目数 data.作业数组
	 */
	public function getAllWorks() {
		$raw = Hws_Record::select();
		response(['count' => count($raw), 'data' => $raw]);
	}

	/**作业系统 - 上传作业
	 *
	 * @param string $form_filename 1 文件上传组件名
	 * @param int $tid 1 任务ID
	 * @param string $path 0 作业路径
	 * @param string $note 0 备注
	 * @return status.状态/错误代码 rid.作业ID msg.错误提示
	 */
	public function uploadWork($form_filename, $tid, $path = 'upload/', $note = null) {
		if ($task = Hws_Task::where('id', '=', $tid)->select()) {
			// TODO: 验证用户的部门，是否符合相应的task
			// 验证提交开放时间
			if (time() >= strtotime($task[0]['start_time']) &&
			time() <= strtotime($task[0]['end_time'])) {
				$src = Document::Documents($form_filename, $path);
				if ($rid = Hws_Record::insert([
					'tid' => $tid,
					'uid' => 1,
					'file_path' => $src,
					'time' => date("Y-m-d H:i:s", time()),
					'note' => $note
				])) {
					response(['status' => 0, 'rid' => $rid[0]]);
				} else {
					response(['status' => -2,  'msg' => '数据插入失败']);
				}
			} else {
				response(['status' => 1,  'msg' => '不在作业开放提交时间内']);
			}
		} else {
			response(['status' => -1,  'msg' => 'tid无效']);
		}
	}

	/**作业系统 - 批改作业
	 * 
	 * @param int $rid 1 作业ID
	 * @param char $score 1 等级/分数
	 * @param string $comment 1 评语
	 * @param bit $recommend 0 是否推荐（1/0）
	 * @return status.状态/错误代码 msg.错误提示
	 */
	public function correctWork($rid, $score, $comment, $recommend = 0) {
		// 验证rid
		if (Hws_Record::where('id', '=', $rid)->select()) {
			Hws_Record::where('id', '=', $rid)->update([
				'score' => $score,
				'comment' => $comment,
				'comment_uid' => 1, // TODO: 获取登录用户UID
				'comment_time' => date("Y-m-d H:i:s", time()),
				'recommend' => $recommend
			]);
			response(['status' => 0]);
		} else {
			response(['status' => -1, 'msg' => 'rid无效']);
		}
	}

	/**作业系统 - 获取所有任务
	 * 
	 * @param string $department 0 部门名称
	 * @return count.条目数 data.任务信息数组
	 */
	public function getAllTasks($department = null) {
		if ($department) {
			$raw = Hws_Task::where('department', '=', $department)->select();
		} else {
			$raw = Hws_Task::select();
		}
		response(['count' => count($raw), 'data' => $raw]);
	}

	/* 下方为管理员功能 */

	/**作业系统 - 设置优秀作业
	 * 
	 * @param int $rid 1 作业ID
	 * @return row.受影响条数
	 */
	public function setExcellentWorks($rid) {
		$raw = null;
		if (is_array($rid)) {
			$raw = Hws_Record::whereIn('id', $rid);
		} else {
			$raw = Hws_Record::where('id', '=', $rid);
		}
		response(['row' => $raw->update(['recommend' => 1])]);
	}

	/**作业系统 - 修改作业信息
	 * 
	 * @param int $rid 1 作业ID
	 * @param array $update 1 修改信息
	 * @return row.受影响条数
	 */
	public function updateWork($rid, $update) {
		response(['row' => Hws_Record::where('id', '=', $rid)->update($update)]);
	}

	/**作业系统 - 删除作业
	 * 
	 * @param int $rid 1 作业ID
	 * @return row.受影响条数
	 */
	public function deleteWork($rid) {
		response(['row' => Hws_Record::where('id', '=', $rid)->delete()]);
	}

	/**作业系统 - 添加任务
	 * 
	 * @param string $title 1 任务标题
	 * @param string $content 1 任务内容
	 * @param string $department 1 归属部门
	 * @param timestamp $end_time 1 截止时间（13位时间戳）
	 * @param timestamp $start_time 0 起始时间（13位时间戳）
	 * @return status.状态/错误代码 tid.任务ID msg.错误提示
	 */
	public function addTask($title, $content, $department, $end_time, $start_time = null) {
		$end_time = $this->getJSTimestamp($end_time);	// 转换时间戳
		$start_time = is_null($start_time) ? time() : $this->getJSTimestamp($start_time);
		if ($end_time < time() || $end_time < $start_time) {
			response(['status' => 1, 'msg' => '截止时间需大于现在和起始时间']);
			return;
		}

		$tid = Hws_Task::insert([
			'title' => $title,
			'content' => $content,
			'department' => $department,
			'start_time' => date("Y-m-d H:i:s", $start_time),
			'end_time' => date("Y-m-d H:i:s", $end_time)
		]);
		response(['status' => 0, 'tid' => $tid[0]]);
	}

	/**作业系统 - 修改任务
	 * 
	 * @param int $tid 1 任务ID
	 * @param array $update 1 修改信息
	 * @return row.受影响条数
	 */
	public function updateTask($tid, $update) {
		response(['row' => Hws_Task::where('id', '=', $tid)->update($update)]);
	}

	/**作业系统 - 删除任务
	 * 
	 * @param int $tid 1 任务ID
	 * @return row.受影响条数
	 */
	public function deleteTask($tid) {
		response(['row' => Hws_Task::where('id', '=', $tid)->delete()]);
	}

	/**作业系统 - 手动截止任务
	 * 
	 * @param int $tid 1 任务ID
	 * @return row.受影响条数
	 */
	public function setTaskOff($tid) {
		response(['row' => Hws_Task::where('id', '=', $tid)->update([
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