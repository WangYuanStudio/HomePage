<?php
namespace App\Lib;

use App\Models\Role;

/*
 * Authorization Control
 * Copyright @ WangYuanStudio
 *
 * Author: laijingwu
 * Last modified time: 2016-08-24 00:40
 *
 * Usage method:
 * use App\Lib\Authorization;
 * 1. Authorization::isAuthorized($role_id, $permission)
 * 2. Authorization::getExistingPermission($role_id)
 */
class Authorization
{
	/**验证权限过程
	 *
	 * @param int $rid 角色ID
	 * @param string $permission 权限名
	 * @return boolean
	 */
	public static function isAuthorized($rid, $permission) {
		// 角色关联至权限
		if ($own = self::getExistingPermission($rid)) {
			// 遍历用户所具有的权限表
			foreach ($own as $value) {
				// 权限表中存在请求的权限
				if (in_array($permission, $value))
					return true;
			}
		}
		return false;
	}

	/**获取角色所具有的权限表
	 * 
	 * @param int $rid 角色ID
	 * @return array
	 */
	public static function getExistingPermission($rid) {
		return Role::belongsToMany("auth")->where('role.id', '=', $rid)->select();
	}
}
?>