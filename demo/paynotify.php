<?php

require_once __DIR__ . "/autoload.php";
use zhangv\unionpay\UnionPay;

list($mode, $config) = include './config.php';
$unionPay = UnionPay::B2C($config, $mode);

$notifyData = $_POST;
$respCode = $notifyData['respCode'];
if ($respCode == '00') {
	$txnType = $notifyData['txnType'];
	if ($txnType == UnionPay::TXNTYPE_CONSUME) {
		$unionPay->onPayNotify($notifyData, 'demoCallback');
	} elseif ($txnType == UnionPay::TXNTYPE_CONSUMEUNDO) {
		$unionPay->onPayUndoNotify($notifyData, 'demoCallback');
	} elseif ($txnType == UnionPay::TXNTYPE_REFUND) {
		$unionPay->onRefundNotify($notifyData, 'demoCallback');
	}else {
		echo 'fail';
	}
} elseif (in_array($respCode, ['03', '04', '05'])) {
	//后续需发起交易状态查询交易确定交易状态
}else {
	echo 'fail';
}


function demoCallback($notifyData) {
	print_r($notifyData);
}