<?php
namespace zhangv\unionpay\service;
use zhangv\unionpay\UnionPay;
/**
 * 无跳转支付(Token版)
 * @license MIT
 * @author zhangv
 * @ref https://open.unionpay.com/ajweb/product/newProApiList?proId=2
 * */
class Token extends Direct {

	/**
	 * 申请token
	 * @param $orderId
	 * @param $tokenPayData
	 * @param array $ext
	 * @return mixed
	 */
	public function applyToken($orderId, $tokenPayData, $ext = []) {
		$params = array_merge($this->commonParams(),[
			//基础参数
			'txnType' => UnionPay::TXNTYPE_APPLYTOKEN,
			'txnSubType' => '05',
			//交易参数
			'orderId' => $orderId,
			'tokenPayData' => $tokenPayData,
			'txnTime' => date('YmdHis')
		],$ext);
		$params['signature'] = $this->sign($params);
		return $this->post($this->backTransUrl, $params);
	}

	/**
	 * 后台开通（需要用申请的商户号，并授权后方可测试）
	 * @param $orderId
	 * @param $accNo
	 * @param $customerInfo
	 * @param array $ext
	 * @return mixed
	 */
	public function backOpen($orderId, $accNo, $customerInfo, $ext = []) {
		$ext['bizType'] = UnionPay::BIZTYPE_TOKEN;
		return parent::backOpen($orderId, $accNo, $customerInfo, $ext);
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
		$ext['bizType'] = UnionPay::BIZTYPE_TOKEN;
		return parent::frontOpen($orderId, $accNo, $customerInfo, $ext);
	}

	/**
	 * 开通通知处理
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
	 * @param array $ext
	 * @return array
	 */
	public function queryOpen($orderId, $accNo, $ext = []) {
		$ext['bizType'] = UnionPay::BIZTYPE_TOKEN;
		return parent::queryOpen($orderId, $accNo, $ext);
	}

	/**
	 * 删除token
	 * @param string $orderId
	 * @param string $tokenPayData
	 * @return array
	 */
	public function deleteToken($orderId, $tokenPayData) {
		$params = array_merge($this->commonParams(),[
			//基础参数
			'txnType' => UnionPay::TXNTYPE_DELETETOKEN,
			'txnSubType' => '01',
			'backUrl' => null,
//			'certId' => null,
			//交易参数
			'orderId' => $orderId,
			'tokenPayData' => $tokenPayData,
			'txnTime' => date('YmdHis')
		]);
		$params['signature'] = $this->sign($params);
		return $this->post($this->backTransUrl, $params);
	}

	/**
	 * 更新token
	 * @param string $orderId
	 * @param array $customerInfo
	 * @param string $tokenPayData
	 * @return array
	 */
	public function updateToken($orderId, $customerInfo, $tokenPayData) {
		$params = array_merge($this->commonParams(),[
			//基础参数
			'txnType' => UnionPay::TXNTYPE_APPLYTOKEN,
			'txnSubType' => '03',
			'backUrl' => null,
			'certId' => null,
			//交易参数
			'orderId' => $orderId,
			'tokenPayData' => $tokenPayData,
			'customerInfo' => $this->encryptCustomerInfo($customerInfo),
			'txnTime' => date('YmdHis')
		]);
		$params['signature'] = $this->sign($params);
		return $this->post($this->backTransUrl, $params);
	}

	/**
	 * 发送短信验证码(开通、支付、预授权)
	 * @param string $orderId
	 * @param string $accNo
	 * @param string $customerInfo
	 * @param string $smsType
	 * @param array $ext
	 * @return array
	 */
	public function sms($orderId, $accNo, $customerInfo, $smsType = Direct::SMSTYPE_OPEN, $ext = []):array{
		$ext['bizType'] = UnionPay::BIZTYPE_TOKEN;
		return parent::sms($orderId, $accNo, $customerInfo, $smsType, $ext);
	}


	/**
	 * 使用Token支付
	 * @param $orderId
	 * @param $txnAmt
	 * @param array $ext
	 * @return array
	 */
	public function payByToken($orderId, $txnAmt, $tokenPayData, $customerInfo, $ext = []) {
		$params = array_merge($this->commonParams(),[
			//基础参数
			'txnType' => UnionPay::TXNTYPE_CONSUME,
			'txnSubType' => '01',
			'currencyCode' =>  $this->config['currencyCode'],
			//交易参数
			'orderId' => $orderId,
			'txnAmt' => $txnAmt,
			'tokenPayData' => $tokenPayData,
			'customerInfo' => $this->encryptCustomerInfo($customerInfo),
			'txnTime' => date('YmdHis')
		],$ext);
		$params['signature'] = $this->sign($params);
		return $this->post($this->backTransUrl, $params);
	}

	/**
	 * 分期付款
	 * @param $orderId
	 * @param $txnAmt
	 * @param array $ext
	 * @return array
	 */
	public function payByInstallment($orderId, $txnAmt, $accNo, $customerInfo, $installmentInfo, $ext = []) {
		return parent::payByInstallment($orderId, $txnAmt, $accNo, $customerInfo, $installmentInfo, $ext);
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
		return parent::frontOpenPay($orderId, $txnAmt, $accNo, $customerInfo, $ext);
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
		return parent::payUndo($orderId, $origQryId, $txnAmt, $ext);
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
		return parent::refund($orderId, $origQryId, $txnAmt, $ext);
	}

	/**
	 * 交易状态查询
	 * @param $orderId
	 * @param $txnTime
	 * @param array $ext
	 * @return mixed
	 */
	public function query($orderId, $txnTime, $ext = []) {
		return parent::query($orderId, $txnTime, $ext);
	}

	/**
	 * 文件传输
	 * @param string $settleDate MMDD
	 * @param string $fileType
	 * @return mixed
	 */
	public function fileDownload($settleDate, $fileType = '00') {
		parent::fileDownload($settleDate, $fileType);
	}

	/**
	 * 通用配置参数
	 * @return array
	 */
	protected function commonParams() {
		return  array_merge(UnionPay::commonParams(),[
			'bizType' => UnionPay::BIZTYPE_TOKEN,
			'accessType' => UnionPay::ACCESSTYPE_MERCHANT,
			'channelType' => UnionPay::CHANNELTYPE_PC,
		]);
	}

}