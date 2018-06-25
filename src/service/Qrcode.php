<?php
namespace zhangv\unionpay\service;
use zhangv\unionpay\UnionPay;

use \Exception;

/**
 * 二维码支付
 * @license MIT
 * @author zhangv
 * @ref https://open.unionpay.com/ajweb/product/newProApiList?proId=89
 * */
class Qrcode extends B2C {

	/**
	 * 二维码申请
	 * @param $orderId
	 * @param $txnAmt
	 * @param array $ext
	 * @return string
	 */
	public function apply($orderId, $txnAmt, $ext = []) {
		$ext['bizType'] = UnionPay::BIZTYPE_QRCODE;
		$ext['channelType'] = UnionPay::CHANNELTYPE_MOBILE;
		$result = parent::pay($orderId, $txnAmt, $ext);
		return $result;
	}

	/**
	 * 支付
	 * @param $orderId
	 * @param $txnAmt
	 * @param array $ext
	 * @return array
	 * @throws Exception
	 */
	public function pay($orderId, $txnAmt, $ext = []) {
		if (empty($ext['termId'])) {
			throw new Exception("termId is required.");
		}
		if (empty($ext['qrNo'])) {
			throw new Exception("qrNo is required.");
		}
		$params = [
			'version' => $this->config['version'],
			'encoding' => $this->config['encoding'],
			'signMethod' => UnionPay::SIGNMETHOD_RSA,
			'txnType' => UnionPay::TXNTYPE_CONSUME,
			'txnSubType' => '06',
			'bizType' => UnionPay::BIZTYPE_QRCODE,
			'channelType' => UnionPay::CHANNELTYPE_MOBILE,
			'backUrl' => $this->config['notifyUrl'],
			'accessType' => '0', //接入类型
			'merId' => $this->config['merId'],
			'orderId' => $orderId,
			'txnTime' => date('YmdHis'),
			'txnAmt' => $txnAmt,
			'currencyCode' => '156',
		];
		$params['certId'] = $this->getSignCertId();
		$params = array_merge($params, $ext);
		$params['signature'] = $this->sign($params);
		return $this->post($this->backTransUrl, $params);
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
		$ext['bizType'] = UnionPay::BIZTYPE_QRCODE;
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
		$ext['bizType'] = UnionPay::BIZTYPE_QRCODE;
		$ext['channelType'] = UnionPay::CHANNELTYPE_MOBILE;
		$result = parent::refund($orderId, $origQryId, $refundAmt, $ext);
		return $result;
	}

	/**
	 * 交易状态查询
	 * @param $orderId
	 * @param $txnTime
	 * @param array $ext
	 * @return mixed
	 */
	public function query($orderId, $txnTime, $ext = []) {
		$ext['bizType'] = UnionPay::BIZTYPE_QRCODE;
		$ext['channelType'] = UnionPay::CHANNELTYPE_MOBILE;
		return parent::query($orderId, $txnTime, $ext);
	}

	/**
	 * 文件传输
	 * @param string $settleDate MMDD
	 * @param string $fileType
	 * @return mixed
	 */
	public function fileDownload($settleDate, $fileType = '00') {
		return parent::fileDownload($settleDate, $fileType);
	}

}