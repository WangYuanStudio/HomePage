<?php
namespace App\Models;

/*
 * Role
 * Copyright @ WangYuanStudio
 *
 * Author: laijingwu
 * Last modified time: 2016-08-18 13:45
 */
class Role extends Model
{
	protected $auth = [
		"Role_Permission" => ["Role_Permission.rid", "Role.id"],
		"Permission" => ["Permission.id", "Role_Permission.pid"]
	];
}
?>