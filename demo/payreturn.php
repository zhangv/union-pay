<?php

require_once __DIR__ . "/../src/UnionPay.php";
require_once __DIR__ . "/../src/HttpClient.php";
use zhangv\unionPay\UnionPay;

list($mode,$config) = include './config.php';
$unionPay = new UnionPay($config,$mode);

$postdata = $_REQUEST;
$unionPay->onPayNotify($postdata,function($notifydata){
	echo 'SUCCESS';
	var_dump($notifydata);
});
