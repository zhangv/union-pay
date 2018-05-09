<?php
namespace zhangv\unionpay\service;
use zhangv\unionpay\UnionPay;
/**
 * 银联手机App支付、ApplePay
 * @license MIT
 * @author zhangv
 * @ref https://open.unionpay.com/ajweb/product/newProApiList?proId=3
 * */
class App extends UnionPay {

	/**
	 * 支付
	 * @param $orderId
	 * @param $txnAmt
	 * @param array $ext
	 * @return array
	 */
	public function pay($orderId,$txnAmt,$ext = []){
		$params = [
			'version' => $this->config['version'],
			'encoding' => $this->config['encoding'],
			'signMethod' => UnionPay::SIGNMETHOD_RSA,
			'txnType' => UnionPay::TXNTYPE_CONSUME,
			'txnSubType' => '01',
			'bizType' => UnionPay::BIZTYPE_B2C,
			'channelType' => UnionPay::CHANNELTYPE_MOBILE,
			'frontUrl' => $this->config['returnUrl'],
			'backUrl' => $this->config['notifyUrl'],
			'accessType' => '0', //接入类型
			'merId' => $this->config['merId'],
			'orderId' => $orderId,
			'txnTime' => date('YmdHis'),
			'txnAmt' => $txnAmt ,
			'currencyCode' => '156',
			'defaultPayType' => '0001',	//默认支付方式
		];
		$params['certId'] =  $this->getSignCertId();
		$params = array_merge($params,$ext);
		$params['signature'] = $this->sign($params);
		return $this->post($params,$this->appTransUrl);
	}

	/**
	 * 消费撤销
	 * @param string $orderId
	 * @param string $origQryId
	 * @param string $txnAmt
	 * @param array $ext
	 * @return mixed
	 */
	public function payUndo($orderId,$origQryId,$txnAmt,$ext = []){
		$result = parent::payUndo($orderId,$origQryId,$txnAmt,$ext);
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
	public function refund($orderId,$origQryId,$refundAmt,$ext = []){
		$result = parent::refund($orderId,$origQryId,$refundAmt,$ext);
		return $result;
	}

	/**
	 * 交易状态查询
	 * @param $orderId
	 * @param array $ext
	 * @return mixed
	 */
	public function query($orderId,$ext = []){
		return parent::query($orderId,$ext);
	}

	/**
	 * 文件传输
	 * @param string $settleDate MMDD
	 * @param string $fileType
	 * @return mixed
	 */
	public function fileDownload($settleDate,$fileType = '00'){
		return parent::fileDownload($settleDate,$fileType);
	}

	/**
	 * 预授权
	 * @param $orderId
	 * @param $amt
	 * @param $orderDesc
	 * @param array $ext
	 * @return mixed
	 */
	public function preAuth($orderId,$amt,$orderDesc,$ext = []){
		$params = array(
			'version' => $this->config['version'],
			'encoding' => $this->config['encoding'],
			'txnType' => UnionPay::TXNTYPE_PREAUTH,
			'txnSubType' => '01',
			'bizType' => UnionPay::BIZTYPE_B2C,
			'frontUrl' =>  $this->config['returnUrl'],
			'backUrl' => $this->config['notifyUrl'],
			'signMethod' => UnionPay::SIGNMETHOD_RSA,
			'channelType' => UnionPay::CHANNELTYPE_MOBILE,
			'accessType' => '0',
			'merId' => $this->config['merId'],
			'orderId' => $orderId,
			'txnTime' => date('YmdHis'),
			'txnAmt' => $amt,
			'currencyCode' => '156',
			'orderDesc' => $orderDesc,
		);
		$params['certId'] =  $this->getSignCertId();
		$params = array_merge($params,$ext);
		$params['signature'] = $this->sign($params);
		$result = $this->post($params,$this->appTransUrl);
		return $result;
	}

	/**
	 * 预授权撤销
	 * @param $orderId
	 * @param $origQryId
	 * @param $txnAmt
	 * @param array $ext
	 * @return array
	 */
	public function preAuthUndo($orderId,$origQryId,$txnAmt,$ext = []){
		return parent::preAuthUndo($orderId,$origQryId,$txnAmt,$ext);
	}

	/**
	 * 预授权完成
	 * @param $orderId
	 * @param $origQryId
	 * @param $txnAmt
	 * @param array $ext
	 * @return array
	 */
	public function preAuthFinish($orderId,$origQryId,$txnAmt,$ext = []){
		return parent::preAuthFinish($orderId,$origQryId,$txnAmt,$ext);
	}

	/**
	 * 预授权完成撤销
	 * @param $orderId
	 * @param $origQryId
	 * @param $txnAmt
	 * @param array $ext
	 * @return array
	 */
	public function preAuthFinishUndo($orderId,$origQryId,$txnAmt,$ext = []){
		return parent::preAuthFinishUndo($orderId,$origQryId,$txnAmt,$ext);
	}

	/**
	 * 对控件支付成功返回的结果信息中data域进行验签
	 * @param string $jsonData json格式数据
	 * @return bool 是否成功
	 */
	public function verifyAppResponse($jsonData) {
		$data = json_decode($jsonData);
		$sign = $data->sign;
		$data = $data->data;
		$public_key = openssl_x509_read(file_get_contents($this->config['verifyCertPath']));
		$signature = base64_decode ( $sign );
		$params_sha1x16 = sha1 ( $data, FALSE );
		$isSuccess = openssl_verify ( $params_sha1x16, $signature,$public_key, OPENSSL_ALGO_SHA1 );
		return $isSuccess;
	}

}