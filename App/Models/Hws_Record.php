<?php
namespace App\Models;

/*
 * Hws_Record
 * Copyright @ WangYuanStudio
 *
 * Author: laijingwu
 * Last modified time: 2016-08-24 00:39
 */
class Hws_Record extends Model
{
	protected $getTask = [
		"Hws_Task" => ["Hws_Task.id", "Hws_Record.tid"]
	];

	protected $getUserInfo = [
		"user" => ["user.id", "Hws_Record.uid"]
	];
}
?>