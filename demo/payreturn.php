<?php

require_once __DIR__ . "/autoload.php";
use zhangv\unionpay\UnionPay;

list($mode, $config) = include './config.php';
$unionPay = UnionPay::B2C($config, $mode);

$postdata = $_REQUEST;
$unionPay->onPayNotify($postdata, function($notifydata) {
	echo 'SUCCESS';
	var_dump($notifydata);
});
