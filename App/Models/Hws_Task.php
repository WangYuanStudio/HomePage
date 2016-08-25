<?php
namespace App\Models;

/*
 * Hws_Task
 * Copyright @ WangYuanStudio
 *
 * Author: laijingwu
 * Last modified time: 2016-08-24 00:38
 */
class Hws_Task extends Model
{
	protected $getWork = [
		"Hws_Record" => ["Hws_Record.tid", "Hws_Task.id"]
	];
}
?>