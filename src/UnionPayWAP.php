<?php
namespace zhangv\unionpay;

/**
 * 银联WAP支付
 * @license MIT
 * @author zhangv
 * @ref https://open.unionpay.com/ajweb/product/detail?id=66
 * */
class UnionPayWAP extends UnionPay {

	public function __construct($config){
		parent::__construct($config);
	}

	/**
	 * 支付
	 * @param $orderId
	 * @param $txnAmt
	 * @param string $reqReserved
	 * @param string $reserved
	 * @param array $ext
	 * @return string
	 */
	public function pay($orderId,$txnAmt,$reqReserved = '',$reserved = '',$ext = []){
		$ext['channelType'] = UnionPay::CHANNELTYPE_MOBILE;
		$result = parent::pay($orderId,$txnAmt,$reqReserved,$reserved,$ext);
		return $result;
	}

	/**
	 * 消费撤销
	 * @param string $orderId
	 * @param string $origQryId
	 * @param string $txnAmt
	 * @param string $reserved
	 * @param string $reqReserved
	 * @param array $ext
	 * @return mixed
	 */
	public function payUndo($orderId,$origQryId,$txnAmt,$reqReserved = '',$reserved = '',$ext = []){
		$ext['channelType'] = UnionPay::CHANNELTYPE_MOBILE;
		$result = parent::payUndo($orderId,$origQryId,$txnAmt,$reqReserved,$reserved,$ext);
		return $result;
	}


	/**
	 * 退款
	 * @param $orderId
	 * @param $origQryId
	 * @param $refundAmt
	 * @param string $reqReserved
	 * @param string $reserved
	 * @param array $ext
	 * @return mixed
	 */
	public function refund($orderId,$origQryId,$refundAmt,$reqReserved = '',$reserved = '',$ext = []){
		$ext['channelType'] = UnionPay::CHANNELTYPE_MOBILE;
		$result = parent::refund($orderId,$origQryId,$refundAmt,$reqReserved,$reserved,$ext);
		return $result;
	}

}