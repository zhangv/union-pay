<?php

require_once __DIR__ . "/../autoload.php";
use zhangv\unionpay\UnionPay;

list($mode,$config) = include '../config.php';
$unionPay = new UnionPay($config,$mode);

$payOrderNo = date('YmdHis');
$amt = 1;
$desc = 'desc';

$html = $unionPay->preAuth($payOrderNo,$amt,$desc);
echo $html;
