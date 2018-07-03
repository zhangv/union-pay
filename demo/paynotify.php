<?php

require_once __DIR__ . "/autoload.php";
use zhangv\unionpay\UnionPay;

list($mode, $config) = include './config.php';
$unionPay = new UnionPay($config, $mode);

$notifyData = $_POST;
$respCode = $notifyData['respCode'];
$result = $unionPay->onNotify($notifyData,'demoCallback');

function demoCallback($notifyData) {
	print_r($notifyData);
}