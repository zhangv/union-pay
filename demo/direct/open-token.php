<?php

require_once __DIR__ . "/../autoload.php";
use zhangv\unionpay\UnionPay;


list($mode, $config) = include '../config-direct.php';
$unionPay = UnionPay::Token($config, $mode);

$payOrderNo = date('YmdHis');
$amt = 2;
$desc = 'desc';
$testAcc = $config['testAcc'][3];
$accNo = $testAcc['accNo'];

$customerInfo = [
	'phoneNo' => $testAcc['phoneNo'], //手机号
	'certifTp' => $testAcc['certifTp'], //证件类型，01-身份证
	'certifId' => $testAcc['certifId'], //证件号，15位身份证不校验尾号，18位会校验尾号，请务必在前端写好校验代码
	'customerNm' => $testAcc['customerNm'], //姓名
];
$ext = ['tokenPayData' => "{trId=62000000001&tokenType=01}"];
$form = $unionPay->frontOpen($payOrderNo, $accNo, $customerInfo, $ext);
echo $form;