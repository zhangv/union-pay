<?php

require_once __DIR__ . "/../src/UnionPay.php";
use zhangv\unionPay\UnionPay;

$config = include './config.php';
$unionPay = new UnionPay($config);
$payOrderNo = date('YmdHis');
$sum = 1;
$desc = 'desc';

$html = $unionPay->consume($payOrderNo,$sum,$desc,'');
echo $html;