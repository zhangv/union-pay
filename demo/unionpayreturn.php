<?php

require_once __DIR__ . "/../UnionPay.php";
$config = include './config.php';

$unionPay = new UnionPay($config);
$unionPay->params = $_POST;//银联提交的参数
if($unionPay->verifySign() && $unionPay->params['respCode'] == '00') {
	echo '支付成功';
}else echo '支付失败';