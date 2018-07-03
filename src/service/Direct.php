<?php
namespace zhangv\unionpay\service;
use zhangv\unionpay\UnionPay;
/**
 * 无跳转支付(标准版)
 * @license MIT
 * @author zhangv
 * @link https://open.unionpay.com/ajweb/product/newProDetail?proId=2&cataId=20
 * @method mixed updatePublicKey($orderId, $ext = [])
 * @method mixed query($orderId, $txnTime, $ext = [])
 * @method mixed fileDownload($settleDate, $fileType)
 */
class Direct extends UnionPay {

	/**
	 * 后台开通（需要用申请的商户号，并授权后方可测试）
	 * @param $orderId
	 * @param $accNo
	 * @param $customerInfo
	 * @param array $ext
	 * @return mixed
	 */
	public function backOpen($orderId, $accNo, $customerInfo, $ext = []) {
		$params = array_merge($this->commonParams(),[
			//基础参数
			'txnType' => UnionPay::TXNTYPE_DIRECTOPEN,
			'txnSubType' => '00',
			//交易参数
			'orderId' => $orderId,
			'accNo' => $this->encryptData($accNo),
			'customerInfo' => $this->encryptCustomerInfo($customerInfo),
			'txnTime' => date('YmdHis'),
		],$ext);
		$params['signature'] = $this->sign($params);
		return $this->post($this->backTransUrl, $params);
	}

	/**
	 * 前台开通
	 * @param $orderId
	 * @param $accNo
	 * @param $customerInfo
	 * @param array $ext
	 * @return string
	 */
	public function frontOpen($orderId, $accNo, $customerInfo, $ext = []) {
		$params = array_merge($this->commonParams(),[
			//基础参数
			'txnType' => UnionPay::TXNTYPE_DIRECTOPEN,
			'txnSubType' => '00',
			//交易参数
			'orderId' => $orderId,
			'accNo' => $this->encryptData($accNo),
			'customerInfo' => $this->encryptCustomerInfo($customerInfo),
			'txnTime' => date('YmdHis'),
			'accType' => '01',
			'frontUrl' => $this->config['openReturnUrl'],
			'backUrl' => $this->config['openNotifyUrl'],
			'payTimeout' => '',
		],$ext);
		$params['signature'] = $this->sign($params);
		$result = $this->createPostForm($params, '开通',null, true);
		return $result;
	}

	/**
	 * 无跳转开通异步通知处理
	 * @param array $notifyData
	 * @param callable $callback
	 * @param bool $validate
	 * @return mixed
	 * @throws \Exception
	 */
	public function onOpenNotify(array $notifyData, callable $callback, bool $validate = true) {
		return parent::onOpenNotify($notifyData,$callback,$validate);
	}

	/**
	 * 查询开通
	 * @param $orderId
	 * @param $accNo
	 * @param $ext
	 * @return array
	 */
	public function queryOpen($orderId, $accNo, $ext = []) {
		$params = array_merge($this->commonParams(),[
			//基础参数
			'txnType' => UnionPay::TXNTYPE_QUERYOPEN,
			'txnSubType' => '00',
			//交易参数
			'orderId' => $orderId,
			'accNo' => $this->encryptData($accNo),
			'txnTime' => date('YmdHis'),
		],$ext);
		$params['signature'] = $this->sign($params);
		return $this->post($this->backTransUrl, $params);
	}

	/**
	 * 发送短信验证码(开通、支付、预授权...)
	 * @link https://open.unionpay.com/ajweb/product/newProApiShow?proId=2&apiId=93
	 * @param $orderId
	 * @param $accNo
	 * @param $customerInfo
	 * @param $smsType
	 * @param array $ext
	 * @return array
	 */
	public function sms($orderId, $accNo, $customerInfo, $smsType = Direct::SMSTYPE_OPEN, $ext = []):array{
		$params = array_merge($this->commonParams(),[
			//基础参数
			'txnType' => UnionPay::TXNTYPE_AUTHENTICATE,
			'txnSubType' => $smsType,
			//交易参数
			'orderId' => $orderId,
			'accNo' => $this->encryptData($accNo),
			'txnTime' => date('YmdHis'),
			'customerInfo' => $this->encryptCustomerInfo($customerInfo)
		],$ext);
		$params['signature'] = $this->sign($params);
		return $this->post($this->backTransUrl, $params);
	}


	/**
	 * 支付
	 * @param $orderId
	 * @param $txnAmt
	 * @param $accNo
	 * @param $customerInfo
	 * @param array $ext
	 * @return array
	 */
	public function pay($orderId, $txnAmt, $accNo, $customerInfo, $ext = []) {
		$params = array_merge($this->commonParams(),[
			//基础参数
			'txnType' => UnionPay::TXNTYPE_CONSUME,
			'txnSubType' => '01',
			'currencyCode' =>  $this->config['currencyCode'],
			//交易参数
			'orderId' => $orderId,
			'txnAmt' => $txnAmt,
			'accNo' => $this->encryptData($accNo),
			'customerInfo' => $this->encryptCustomerInfo($customerInfo),
			'txnTime' => date('YmdHis')
		],$ext);
		$params['signature'] = $this->sign($params);
		return $this->post($this->backTransUrl, $params);
	}

	/**
	 * 分期付款
	 * @param string $orderId
	 * @param string $txnAmt
	 * @param string $accNo
	 * @param string $customerInfo
	 * @param string $installmentInfo
	 * @param array $ext
	 * @return array
	 */
	public function payByInstallment($orderId, $txnAmt, $accNo, $customerInfo, $installmentInfo, $ext = []) {
		$params = array_merge($this->commonParams(),[
			//基础参数
			'txnType' => UnionPay::TXNTYPE_CONSUME,
			'txnSubType' => '03',
			'currencyCode' =>  $this->config['currencyCode'],
			//交易参数
			'orderId' => $orderId,
			'txnAmt' => $txnAmt,
			'accNo' => $this->encryptData($accNo),
			'customerInfo' => $this->encryptCustomerInfo($customerInfo),
			'txnTime' => date('YmdHis'),
			'backUrl' => $this->config['notifyUrl'],
			//分期付款用法（商户自行设计分期付款展示界面）：
			//【生产环境】支持的银行列表清单请联系银联业务运营接口人索要
			'instalTransInfo' => $installmentInfo
		],$ext);
		$params['signature'] = $this->sign($params);
		return $this->post($this->backTransUrl, $params);
	}

	/**
	 * 前台开通并支付
	 * @param $orderId
	 * @param $accNo
	 * @param $customerInfo
	 * @param array $ext
	 * @return string
	 */
	public function frontOpenPay($orderId, $txnAmt, $accNo, $customerInfo, $ext = []) {
		$params = array_merge($this->commonParams(),[
			//基础参数
			'txnType' => UnionPay::TXNTYPE_CONSUME,
			'txnSubType' => '01',
			'currencyCode' =>  $this->config['currencyCode'],
			//交易参数
			'orderId' => $orderId,
			'txnAmt' => $txnAmt,
			'accNo' => $this->encryptData($accNo),
			'customerInfo' => $this->encryptCustomerInfo($customerInfo),
			'txnTime' => date('YmdHis'),
			'accType' => '01',
			'frontUrl' => $this->config['openReturnUrl'],
			'backUrl' => $this->config['openNotifyUrl'],
			'payTimeout' => ''
		],$ext);
		$params['signature'] = $this->sign($params);
		$result = $this->createPostForm($params, '开通并支付',null,true);
		return $result;
	}

	/**
	 * 支付撤销
	 * @param $orderId
	 * @param $origQryId
	 * @param $txnAmt
	 * @param array $ext
	 * @return array
	 */
	public function payUndo($orderId, $origQryId, $txnAmt, $ext = []) {
		$params = array_merge($this->commonParams(),[
			//基础参数
			'txnType' => UnionPay::TXNTYPE_CONSUMEUNDO,
			'txnSubType' => '01',
			//交易参数
			'orderId' => $orderId,
			'txnAmt' => $txnAmt,
			'origQryId' => $origQryId,
			'txnTime' => date('YmdHis')
		],$ext);
		$params['signature'] = $this->sign($params);
		return $this->post($this->backTransUrl, $params);
	}

	/**
	 * 退款
	 * @param $orderId
	 * @param $origQryId
	 * @param $txnAmt
	 * @param array $ext
	 * @return array
	 */
	public function refund($orderId, $origQryId, $txnAmt, $ext = []) {
		$params = array_merge($this->commonParams(),[
			//基础参数
			'txnType' => UnionPay::TXNTYPE_REFUND,
			'txnSubType' => '00',
			//交易参数
			'orderId' => $orderId,
			'txnAmt' => $txnAmt,
			'origQryId' => $origQryId,
			'txnTime' => date('YmdHis')
		],$ext);
		$params['signature'] = $this->sign($params);
		return $this->post($this->backTransUrl, $params);
	}

	/**
	 * 通用配置参数
	 * @return array
	 */
	protected function commonParams() {
		return  array_merge(UnionPay::commonParams(),[
			'bizType' => UnionPay::BIZTYPE_DIRECT,
			'accessType' => UnionPay::ACCESSTYPE_MERCHANT,
			'channelType' => UnionPay::CHANNELTYPE_PC,
		]);
	}

}