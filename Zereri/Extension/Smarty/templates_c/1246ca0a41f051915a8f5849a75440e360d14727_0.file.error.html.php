<?php
/* Smarty version 3.1.30-dev/72, created on 2016-08-19 08:26:18
  from "C:\Users\huizhe\Documents\HomePage\App\Tpl\error.html" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.30-dev/72',
  'unifunc' => 'content_57b6c2aa2b21b1_71189959',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '1246ca0a41f051915a8f5849a75440e360d14727' => 
    array (
      0 => 'C:\\Users\\huizhe\\Documents\\HomePage\\App\\Tpl\\error.html',
      1 => 1471595115,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_57b6c2aa2b21b1_71189959 (Smarty_Internal_Template $_smarty_tpl) {
?>
<!DOCTYPE html>
<html>
<head>
    <title>Error</title>
    <meta charset="utf-8">
    <style type="text/css">
        *{
            margin: 0;
            padding: 0;
        }
        body{
            background-color: #f0f1f3;
        }
        .container{
            position: absolute;
            left: 50%;
            top: 50%;
            width: 854px;
            height: 385px;
            margin-left: -427px;
            margin-top: -196px;
            background-color: #fff;
            border-top: 8px solid #93d9b0;
        }
        .title{
            width: 755px;
            height: 137px;
            margin: 0 auto;
            font-size: 40px;
            font-family: "华文隶书";
            line-height: 200px;
            border-bottom: 1px solid #a7a7a7;
        }
        .word{
            width: 755px;
            height: 216px;
            margin: 33px auto 0px auto;
            font-size: 17px;
            color: #989898;
            font-family: "Adobe 黑体 Std";
            line-height: 27px;
            letter-spacing: 2px;
            word-wrap : break-word;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="title">
        Something Wrong！
    </div>
    <div class="word">
        <?php echo $_smarty_tpl->tpl_vars['content']->value;?>

    </div>
</div>
</body>
</html><?php }
}
