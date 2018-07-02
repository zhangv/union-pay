<?php

require_once __DIR__ . "/../autoload.php";

use zhangv\unionpay\UnionPay;

list($mode, $config) = include '../config-direct.php';
$unionPay = UnionPay::Charge($config, $mode);

$orderId = date('YmdHis');
$amt = 1;

$html = $unionPay->frontRepay($orderId, $amt, '6226388000000095','张三');
echo $html;
