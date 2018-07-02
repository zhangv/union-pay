<?php
namespace zhangv\unionpay\service;
use zhangv\unionpay\UnionPay;

/**
 * 网关支付
 * @license MIT
 * @author zhangv
 * @ref https://open.unionpay.com/ajweb/product/newProApiList?proId=1
 * @method mixed updatePublicKey($orderId, $ext = [])
 * @method mixed query($orderId, $txnTime, $ext = [])
 * @method mixed fileDownload($settleDate, $fileType = '00')
 */
class B2C extends UnionPay {

	/**
	 * 支付
	 * @param $orderId
	 * @param $txnAmt
	 * @param array $ext
	 * @return string
	 */
	public function pay($orderId, $txnAmt, $ext = []) {
		$params = array_merge($this->commonParams(),[
			//基础参数
			'txnType' => UnionPay::TXNTYPE_CONSUME,
			'txnSubType' => '01',
			'currencyCode' => $this->config['currencyCode'],
			'defaultPayType' => '0001', //默认支付方式
			//交易参数
			'orderId' => $orderId,
			'frontUrl' =>  $this->config['returnUrl'],
			'txnAmt' => $txnAmt,
			'txnTime' => date('YmdHis'),
		],$ext);
		$params['signature'] = $this->sign($params);
		return $this->submitForm($this->frontTransUrl,$params);
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
		$params = array_merge($this->commonParams(),[
			//基础参数
			'txnType' => UnionPay::TXNTYPE_CONSUMEUNDO,
			'txnSubType' => '00',
			//交易参数
			'orderId' => $orderId,
			'origQryId' => $origQryId,
			'txnAmt' => $txnAmt,
			'txnTime' => date('YmdHis'),
		],$ext);

		$params['signature'] = $this->sign($params);
		return $this->post($this->backTransUrl, $params);
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
		$params = array_merge($this->commonParams(),[
			//基础参数
			'txnType' => UnionPay::TXNTYPE_REFUND,
			'txnSubType' => '00',
			//交易参数
			'orderId' => $orderId,
			'origQryId' => $origQryId,
			'txnAmt' => $refundAmt,
			'txnTime' => date('YmdHis'),
		],$ext);
		$params['signature'] = $this->sign($params);
		return $this->post($this->backTransUrl, $params);
	}

	/**
	 * 预授权
	 * @param $orderId
	 * @param $amt
	 * @param $orderDesc
	 * @param array $ext
	 * @return mixed
	 */
	public function preAuth($orderId, $amt, $orderDesc, $ext = []) {
		$params = array_merge($this->commonParams(),[
			//基础参数
			'txnType' => UnionPay::TXNTYPE_PREAUTH,
			'txnSubType' => '01',
			'currencyCode' => $this->config['currencyCode'],
			//交易参数
			'orderId' => $orderId,
			'frontUrl' =>  $this->config['returnUrl'],
			'txnAmt' => $amt,
			'txnTime' => date('YmdHis'),
			'orderDesc' => $orderDesc,
		],$ext);
		$params['signature'] = $this->sign($params);
		return $this->submitForm($this->frontTransUrl,$params);
	}

	/**
	 * 预授权撤销
	 * @param $orderId
	 * @param $origQryId
	 * @param $txnAmt
	 * @param array $ext
	 * @return array
	 */
	public function preAuthUndo($orderId, $origQryId, $txnAmt, $ext = []) {
		$params = array_merge($this->commonParams(),[
			//基础参数
			'txnType' => UnionPay::TXNTYPE_PREAUTHUNDO,
			'txnSubType' => '00',
			'bizType' => '000000', //overwrite
			'currencyCode' => $this->config['currencyCode'],
			//交易参数
			'orderId' => $orderId,
			'origQryId' => $origQryId, //原预授权的queryId，可以从查询接口或者通知接口中获取
			'txnAmt' => $txnAmt, //交易金额，需和原预授权一致
			'txnTime' => date('YmdHis'),
		],$ext);
		$params['signature'] = $this->sign($params);
		return $this->post($this->backTransUrl, $params);
	}

	/**
	 * 预授权完成
	 * @param $orderId
	 * @param $origQryId
	 * @param $amt
	 * @param array $ext
	 * @return array
	 */
	public function preAuthFinish($orderId, $origQryId, $amt, $ext = []) {
		$params = array_merge($this->commonParams(),[
			//基础参数
			'txnType' => UnionPay::TXNTYPE_PREAUTHFINISH,
			'txnSubType' => '00',
			//交易参数
			'orderId' => $orderId,
			'origQryId' => $origQryId, //原预授权的queryId，可以从查询接口或者通知接口中获取
			'txnAmt' => $amt, //交易金额，需和原预授权一致
			'txnTime' => date('YmdHis'),
		],$ext);
		$params['signature'] = $this->sign($params);
		return $this->post($this->backTransUrl, $params);
	}

	/**
	 * 预授权完成撤销
	 * @param $orderId
	 * @param $origQryId
	 * @param $txnAmt
	 * @param array $ext
	 * @return array
	 */
	public function preAuthFinishUndo($orderId, $origQryId, $txnAmt, $ext = []) {
		$params = array_merge($this->commonParams(),[
			//基础参数
			'txnType' => UnionPay::TXNTYPE_PREAUTHFINISHUNDO,
			'txnSubType' => '00',
			'bizType' => '000000',//overwrite
			//交易参数
			'orderId' => $orderId,
			'origQryId' => $origQryId, //原预授权的queryId，可以从查询接口或者通知接口中获取
			'txnAmt' => $txnAmt, //交易金额，需和原预授权一致
			'txnTime' => date('YmdHis'),
		],$ext);
		$params['signature'] = $this->sign($params);
		return $this->post($this->backTransUrl, $params);
	}

	/**
	 * 支付异步通知处理
	 * @param array $notifyData
	 * @param callable $callback
	 * @param bool $validate
	 * @return mixed
	 * @throws \Exception
	 */
	public function onPayNotify(array $notifyData, callable $callback, bool $validate = true) {
		return parent::onPayNotify($notifyData,$callback,$validate);
	}

	/**
	 * 退款异步通知处理
	 * @param array $notifyData
	 * @param callable $callback
	 * @param bool $validate
	 * @return mixed
	 * @throws \Exception
	 */
	public function onRefundNotify(array $notifyData, callable $callback, bool $validate = true) {
		return parent::onRefundNotify($notifyData,$callback,$validate);
	}

	/**
	 * 消费撤销异步通知处理
	 * @param array $notifyData
	 * @param callable $callback
	 * @param bool $validate
	 * @return mixed
	 * @throws \Exception
	 */
	public function onPayUndoNotify(array $notifyData, callable $callback, bool $validate = true) {
		return parent::onPayUndoNotify($notifyData,$callback,$validate);
	}

	/**
	 * 通用配置参数
	 * @return array
	 */
	protected function commonParams() {
		return  array_merge(UnionPay::commonParams(),[
			'bizType' => UnionPay::BIZTYPE_B2C,
			'accessType' => UnionPay::ACCESSTYPE_MERCHANT,
			'channelType' => UnionPay::CHANNELTYPE_PC,
		]);
	}

}