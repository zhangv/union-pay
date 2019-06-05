<?php
namespace zhangv\unionpay\service;
use zhangv\unionpay\UnionPay;
/**
 * 银联手机App支付、ApplePay
 * @license MIT
 * @author zhangv
 * @link https://open.unionpay.com/ajweb/product/newProApiList?proId=3
 *
 * @method mixed updatePublicKey($orderId, $ext = [])
 * @method mixed query($orderId, $txnTime, $ext = [])
 * @method mixed payUndo($orderId, $origQryId, $txnAmt, $ext = [])
 * @method mixed refund($orderId, $origQryId, $refundAmt, $ext = [])
 * @method mixed preAuthUndo($orderId, $origQryId, $txnAmt, $ext = [])
 * @method mixed preAuthFinish($orderId, $origQryId, $txnAmt, $ext = [])
 * @method mixed preAuthFinishUndo($orderId, $origQryId, $txnAmt, $ext = [])
 * @method mixed fileDownload($settleDate, $fileType = '00')
 * */
class App extends B2C {

	/**
	 * 支付
	 * @param $orderId
	 * @param $txnAmt
	 * @param array $ext
	 * @param bool $serverSide
	 * @return array
	 */
	public function pay($orderId, $txnAmt, $ext = [],$serverSide = false) {
		$params = array_merge($this->commonParams(),[
			'txnType' => UnionPay::TXNTYPE_CONSUME,
			'txnSubType' => '01',
			'currencyCode' => $this->config['currencyCode'],

			'frontUrl' => $this->config['returnUrl'],
			'backUrl' => $this->config['notifyUrl'],
			'orderId' => $orderId,
			'txnTime' => date('YmdHis'),
			'txnAmt' => $txnAmt,
		],$ext);
		$params['signature'] = $this->sign($params);
		return $this->post($this->appTransUrl,$params);
	}

	/**
	 * 预授权
	 * @param $orderId
	 * @param $amt
	 * @param $orderDesc
	 * @param array $ext
	 * @param bool $serverSide
	 * @return mixed
	 */
	public function preAuth($orderId, $amt, $orderDesc, $ext = [],$serverSide = false) {
		$params = array_merge($this->commonParams(),[
			'txnType' => UnionPay::TXNTYPE_PREAUTH,
			'txnSubType' => '01',
			'currencyCode' => $this->config['currencyCode'],
			'frontUrl' =>  $this->config['returnUrl'],
			'backUrl' => $this->config['notifyUrl'],
			'orderId' => $orderId,
			'txnTime' => date('YmdHis'),
			'txnAmt' => $amt,
			'orderDesc' => $orderDesc,
		],$ext);
		$params['signature'] = $this->sign($params);
		return $this->post($this->appTransUrl,$params);
	}

	/**
	 * 对控件支付成功返回的结果信息中data域进行验签
	 * @param string $jsonData json格式数据
	 * @return bool
	 */
	public function verifyAppResponse($jsonData) {
		$data = json_decode($jsonData);
		$sign = $data->sign;
		$data = $data->data;
		$public_key = openssl_x509_read(file_get_contents($this->config['verifyCertPath']));
		$signature = base64_decode($sign);
		$params_sha1x16 = sha1($data, FALSE);
		$isSuccess = openssl_verify($params_sha1x16, $signature, $public_key, OPENSSL_ALGO_SHA1);
		return ($isSuccess === 1) ? true:false;
	}

	/**
	 * 通用配置参数
	 * @return array
	 */
	protected function commonParams() {
		return  array_merge(parent::commonParams(),[
			'bizType' => UnionPay::BIZTYPE_DEFAULT,
			'accessType' => UnionPay::ACCESSTYPE_MERCHANT,
			'channelType' => UnionPay::CHANNELTYPE_MOBILE,
		]);
	}

}