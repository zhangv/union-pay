<?php

require_once __DIR__ . "/../UnionPay.php";
$config = include './config.php';
$unionPay = new UnionPay($config);
$payOrderNo = date('YmdHis');
$sum = 1;
$desc = 'desc';

$html = $unionPay->pay($payOrderNo,$sum,$desc,'');
echo $html;