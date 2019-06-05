<?php
namespace zhangv\unionpay\service;
use zhangv\unionpay\UnionPay;

/**
 * 企业网银支付
 * @license MIT
 * @author zhangv
 * @link https://open.unionpay.com/ajweb/product/newProApiList?proId=65
 * @method mixed refund($orderId, $origQryId, $refundAmt, $ext = [])
 * @method mixed query($orderId, $txnTime, $ext = [])
 * @method mixed fileDownload($settleDate, $fileType = '00')
 */
class B2B extends B2C {

	/**
	 * 支付
	 * @param $orderId
	 * @param $txnAmt
	 * @param array $ext
	 * @return string
	 */
	public function pay($orderId, $txnAmt, $ext = [],$serverSide = false) {
		$ext['bizType'] = UnionPay::BIZTYPE_B2B;
		return parent::pay($orderId, $txnAmt, $ext,$serverSide);
	}

}