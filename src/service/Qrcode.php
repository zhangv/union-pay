<?php
namespace zhangv\unionpay\service;
use zhangv\unionpay\UnionPay;

use \Exception;

/**
 * 二维码支付
 * @license MIT
 * @author zhangv
 * @link https://open.unionpay.com/ajweb/product/newProApiList?proId=89
 * @method mixed fileDownload($settleDate, $fileType = '00')
 */
class Qrcode extends B2C {

	/**
	 * 二维码申请
	 * @param string $orderId
	 * @param string $txnAmt
	 * @param array $ext
	 * @return array
	 */
	public function apply($orderId, $txnAmt, $ext = []) {
		$params = array_merge(parent::commonParams(),[
			'txnType' => UnionPay::TXNTYPE_CONSUME,
			'txnSubType' => '07',
			'bizType' => UnionPay::BIZTYPE_DEFAULT,
			'channelType' => UnionPay::CHANNELTYPE_MOBILE,
			'accessType' => UnionPay::ACCESSTYPE_MERCHANT,
			'currencyCode' => $this->config['currencyCode'],
			'orderId' => $orderId,
			'txnTime' => date('YmdHis'),
			'txnAmt' => $txnAmt,
		],$ext);
		$params['signature'] = $this->sign($params);
		return $this->post($this->backTransUrl, $params);
	}

	/**
	 * 二维码消费（被扫）
	 * @param string $orderId
	 * @param string $txnAmt
	 * @param array $ext
	 * @return array
	 * @throws Exception
	 */
	public function pay($orderId, $txnAmt, $ext = []) {
		if (empty($ext['qrNo'])) {
			throw new Exception("qrNo is required.");
		}
		$params = array_merge(parent::commonParams(),[
			'txnType' => UnionPay::TXNTYPE_CONSUME,
			'txnSubType' => '06',
			'bizType' => UnionPay::BIZTYPE_DEFAULT,
			'channelType' => UnionPay::CHANNELTYPE_MOBILE,
			'accessType' => UnionPay::ACCESSTYPE_MERCHANT,
			'currencyCode' => $this->config['currencyCode'],
			'orderId' => $orderId,
			'txnTime' => date('YmdHis'),
			'txnAmt' => $txnAmt,
		],$ext);
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
		$ext['bizType'] = UnionPay::BIZTYPE_DEFAULT;
		$ext['channelType'] = UnionPay::CHANNELTYPE_MOBILE;
		return parent::payUndo($orderId, $origQryId, $txnAmt, $ext);
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
		$ext['bizType'] = UnionPay::BIZTYPE_DEFAULT;
		$ext['channelType'] = UnionPay::CHANNELTYPE_MOBILE;
		return parent::refund($orderId, $origQryId, $refundAmt, $ext);
	}

	/**
	 * 交易状态查询
	 * @param $orderId
	 * @param $txnTime
	 * @param array $ext
	 * @return mixed
	 */
	public function query($orderId, $txnTime, $ext = []) {
		$ext['bizType'] = UnionPay::BIZTYPE_DEFAULT;
		$ext['channelType'] = UnionPay::CHANNELTYPE_MOBILE;
		return parent::query($orderId, $txnTime, $ext);
	}

}