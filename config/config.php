<?php
$config = new stdclass();
$config->timezone   = 'Asia/Shanghai';
$config->debug      = true;
$config->charset    = 'UTF-8';
$config->sessionVar = 'minisid';
$config->webRoot    = getWebRoot();

$config->db = new stdclass();
$config->db->persistant  = false;     // 是否为持续连接。       Pconnect or not.
$config->db->driver      = 'mysql';   // 目前只支持MySQL数据库。Must be MySQL. Don't support other database server yet.
$config->db->encoding    = 'UTF8';    // 数据库编码。           Encoding of database.
$config->db->strictMode  = false;     // 关闭MySQL的严格模式。  Turn off the strict mode of MySQL.


$myConfig = __DIR__ . DS . 'my.php';
if(file_exists($myConfig)) include $myConfig;
