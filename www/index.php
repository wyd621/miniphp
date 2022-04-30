<?php
error_reporting(E_ALL);
define('DS', DIRECTORY_SEPARATOR);

ob_start();
ini_set("session.cookie_httponly", 1);

include '../framework/router.class.php';
include '../framework/control.class.php';
include '../framework/model.class.php';
include '../framework/helper.class.php';

$app    = new router();
$common = $app->loadCommon();

$app->parseRequest();
$app->loadModule();

echo ob_get_clean();
