<?php
//前台开通并支付
require_once __DIR__ . "/../autoload.php";
use zhangv\unionpay\UnionPay;

list($mode,$config) = include '../config-direct.php';
$unionPay = UnionPay::Direct($config,$mode);

$payOrderNo = date('YmdHis');
$amt = 1;
$desc = 'desc';
$testAcc = $config['testAcc'][3];
$accNo = $testAcc['accNo'];

$customerInfo = [
	'phoneNo' => $testAcc['phoneNo'], //手机号
	'certifTp' => $testAcc['certifTp'], //证件类型，01-身份证
	'certifId' => $testAcc['certifId'], //证件号，15位身份证不校验尾号，18位会校验尾号，请务必在前端写好校验代码
	'customerNm' => $testAcc['customerNm'], //姓名
];

$form = $unionPay->frontOpenPay($payOrderNo,1,$accNo,$customerInfo);
echo $form;
