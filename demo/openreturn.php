<?php

require_once __DIR__ . "/autoload.php";
use zhangv\unionpay\UnionPay;

list($mode, $config) = include './config.php';
$unionPay = UnionPay::Direct($config, $mode);

$postdata = $_REQUEST;
$unionPay->onOpenNotify($postdata, function($notifydata) {
	echo 'SUCCESS';
	var_dump($notifydata);
});
