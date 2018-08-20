<?php
$subdomain = '';

if ( !$subdomain )
{
  die('please set welante subdomain in index.php');	
}

require('proxy.php');

// get route relative to script name
$_GET['route'] = '/' . str_replace('/welante-api/', '', $_SERVER['REQUEST_URI']);

$proxy = new AjaxProxy('https://' . $subdomain . '/api.php');

$proxy->execute();
