<?php

require_once __DIR__ . "/../src/UnionPay.php";
require_once __DIR__ . "/../src/HttpClient.php";
require_once __DIR__ . "/../src/UnionPayDirect.php";

use zhangv\unionPay\UnionPayDirect;

list($mode,$config) = include './config.php';
$unionPay = new UnionPayDirect($config,$mode);

$postdata = $_REQUEST;
$unionPay->onOpenNotify($postdata,function($notifydata){
	echo 'SUCCESS';
	var_dump($notifydata);
});
