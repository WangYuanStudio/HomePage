<?php
namespace App\Models;

class Role extends Model
{
	protected $auth = [
		"Role_Permission" => ["Role_Permission.rid", "Role.id"],
		"Permission" => ["Permission.id", "Role_Permission.pid"]
	];
}


?>