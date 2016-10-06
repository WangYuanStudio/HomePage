<?php
namespace App\Controllers;

use Zereri\Lib\Document;

class Api
{
    public function index()
    {
        (new Document())->init();
    }
}