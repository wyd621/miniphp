<?php
$webRoot      = $this->config->getWebRoot;
$jsRoot       = $webRoot . "js/";
$themeRoot    = $webRoot . "theme/";
$defaultTheme = $webRoot . 'theme/default/';
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset='utf-8'>
  <meta http-equiv='X-UA-Compatible' content='IE=edge'>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="renderer" content="webkit">
  <?php
  echo "<title>{$title}</title>";
  echo "<link rel='stylesheet' href='{$defaultTheme}style.css' type='text/css' media='screen' />";
  echo "<script src='{$jsRoot}jquery.js'></script>";
  echo "<script src='{$jsRoot}my.full.js'></script>";
  ?>
</head>
<body>
