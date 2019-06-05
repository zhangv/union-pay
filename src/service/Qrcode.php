<?php
namespace zhangv\unionpay\service;
use zhangv\unionpay\UnionPay;

use \Exception;

/**
 * 二维码支付(v2.2)
 * @license MIT
 * @author zhangv
 * @link https://open.unionpay.com/ajweb/product/newProApiList?proId=89
 * @method mixed fileDownload($settleDate, $fileType = '00')
 */
class Qrcode extends B2C {

	/**
	 * 二维码申请(主扫)
	 * @param string $orderId
	 * @param array $termInfo 终端信息
	 * @param string $txnAmt 交易金额，若不出现则需要对方输入金额
	 * @param array $ext 扩展数组
	 * @return array
	 */
	public function apply($orderId, $termInfo, $txnAmt = null, $ext = []) {
		$params = array_merge(parent::commonParams(),[
			'txnType' => UnionPay::TXNTYPE_CONSUME,
			'txnSubType' => '07',
			'bizType' => UnionPay::BIZTYPE_DEFAULT,
			'channelType' => UnionPay::CHANNELTYPE_MOBILE,
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
	 * @param bool $serverSide
	 * @return array
	 * @throws Exception
	 */
	public function pay($orderId, $txnAmt, $ext = [],$serverSide = false) {
		if (empty($ext['qrNo'])) {
			throw new Exception("qrNo is required.");
		}
		$params = array_merge(parent::commonParams(),[
			'txnType' => UnionPay::TXNTYPE_CONSUME,
			'txnSubType' => '06',
			'bizType' => UnionPay::BIZTYPE_DEFAULT,
			'channelType' => UnionPay::CHANNELTYPE_MOBILE,
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
	 * 交易查询
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

	/**
	 * 冲正
	 * 必须与原始消费在同一天（准确讲是昨日23:00至本日23:00之间）。 冲正交易，仅用于超时无应答等异常场景，只有发生支付系统超时或者支付结果未知时可调用冲正
	 * @param string $orderId 商户订单号
	 * @param string $txnTime 原始交易时间戳 格式：YmdHis
	 * @param array $ext 扩展
	 * @return mixed
	 */
	public function reverse($orderId, $txnTime, $ext = []) {
		$params = array_merge($this->commonParams(),[
			//基础参数
			'txnType' => UnionPay::TXNTYPE_REVERSE,
			'txnSubType' => '01',
			//交易参数
			'orderId' => $orderId,
			'txnTime' => $txnTime
		],$ext);
		$params['signature'] = $this->sign($params);
		return $this->post($this->backTransUrl, $params);
	}

}