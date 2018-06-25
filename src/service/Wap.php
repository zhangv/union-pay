<?php
namespace zhangv\unionpay\service;
use zhangv\unionpay\UnionPay;

/**
 * 手机网页支付
 * @license MIT
 * @author zhangv
 * @ref https://open.unionpay.com/ajweb/product/detail?id=66
 * */
class Wap extends B2C {

	/**
	 * 支付
	 * @param $orderId
	 * @param $txnAmt
	 * @param array $ext
	 * @return string
	 */
	public function pay($orderId, $txnAmt, $ext = []) {
		$ext['channelType'] = UnionPay::CHANNELTYPE_MOBILE;
		$result = parent::pay($orderId, $txnAmt, $ext);
		return $result;
	}

	/**
	 * 消费撤销
	 * @param string $orderId
	 * @param string $origQryId
	 * @param string $txnAmt
	 * @param array $ext
	 * @return mixed
	 */
	public function payUndo($orderId, $origQryId, $txnAmt, $ext = []) {
		$ext['channelType'] = UnionPay::CHANNELTYPE_MOBILE;
		$result = parent::payUndo($orderId, $origQryId, $txnAmt, $ext);
		return $result;
	}

	/**
	 * 退款
	 * @param $orderId
	 * @param $origQryId
	 * @param $refundAmt
	 * @param array $ext
	 * @return mixed
	 */
	public function refund($orderId, $origQryId, $refundAmt, $ext = []) {
		$ext['channelType'] = UnionPay::CHANNELTYPE_MOBILE;
		$result = parent::refund($orderId, $origQryId, $refundAmt, $ext);
		return $result;
	}

}