<?php
namespace App\Controllers;

use App\Models\Hws_Record;
use App\Models\Hws_Task;

/*
 * Homework
 * Copyright @ WangYuanStudio
 *
 * Author: laijingwu
 * Last modified time: 2016-08-09 13:14
 */
class Homework
{
	//public $middle = "authorization";

	/**获取优秀作业
	 * 
	 * @param int $uid 用户ID
	 * @param int $pageIndex 当前页数
	 * @param int $itemCount 每页项数
	 * @return array.优秀作业
	 */
	private function getExcellentWorksRaw($uid = 0, $pageIndex = 0, $itemCount = 0) {
		$raw = Hws_Record::where('recommend', '=', 1)
			->andWhere('uid', '=', $uid)
			->orderBy('time desc');

		if ($pageIndex > 0 && $itemCount > 0)
			$raw = $raw->limit(($pageIndex - 1) * $itemCount, $pageIndex * $itemCount);
		
		$raw = $raw->select();
		$rawCount = count($raw);
		return $raw;
	}

	/**获取某用户优秀作业
	 * 
	 * @param int $uid 用户ID
	 * @param int $pageIndex 当前页数（不使用可不传）
	 * @param int $itemCount 每页项数（不使用可不传）
	 * @return json.用户优秀作业数组
	 */
	public function getExcellentWorksFromUid($uid, $pageIndex = 0, $itemCount = 0) {
		response($this->getExcellentWorksRaw($uid, $pageIndex, $itemCount));
	}

	/**获取全部优秀作业
	 * 
	 * @param int $pageIndex 当前页数（不使用可不传）
	 * @param int $itemCount 每页项数（不使用可不传）
	 * @return json.优秀作业数组
	 */
	public function getExcellentWorks($pageIndex = 0, $itemCount = 0) {
		response($this->getExcellentWorksRaw(0, $pageIndex, $itemCount));
	}

	/**获取某任务的所有作业
	 * 
	 * @param int/array $tid 作业ID
	 * @return json.作业数组
	 */
	public function getWorksFromTid($tid) {
		$raw = null;
		if (is_array($tid)) {
			$raw = Hws_Record::whereIn('tid', $tid);
		} else {
			$raw = Hws_Record::where('tid', '=', $tid);
		}
		response($raw->select());
	}

	/**获取某用户作业
	 * 
	 * @param int $uid 用户ID
	 * @return json.用户作业数组
	 */
	public function getWorksFromUid($uid) {
		response(Hws_Record::where('uid', '=', $uid)->select());
	}

	/**上传作业
	 *
	 * @param string $department 部门
	 * @param string $path 作业路径
	 * @param string $note 备注
	 * @return json.test
	 */
	public function uploadWork($department, $tid, $path, $note) {
		//
	}

	/* 下方为管理员功能 */

	/**设置优秀作业
	 * 
	 * @param [type] $tid [description]
	 */
	public function setExcellentWorks($tid) {
		$raw = null;
		if (is_array($tid)) {
			$raw = Hws_Record::whereIn('tid', $tid);
		} else {
			$raw = Hws_Record::where('tid', '=', $tid);
		}
		response($raw->update(['recommend' => 1]));
	}

	public function getAllWorks() {
		response(Hws_Record::select());
	}

	public function correctWork($tid, $score, $comment, $comment_uid, $recommend = 0) {
		//comment_uid 可获取登录用户的uid
	}
}
?>