<?php

require_once __DIR__ . "/../autoload.php";

use zhangv\unionpay\UnionPay;

list($mode,$config) = include '../config.php';
$unionPay = UnionPay::B2C($config,$mode);

$orderId = date('YmdHis');
$amt = 1;

$html = $unionPay->pay($orderId,$amt);
echo $html;
