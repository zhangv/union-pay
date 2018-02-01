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
	 * @param array $ext
	 * @return string
	 */
	public function pay($orderId,$txnAmt,$reqReserved = '',$ext = []){
		$params = [
			'version' => '5.0.0',
			'encoding' => 'UTF-8',
			'certId' => $this->getSignCertId(),
			'signMethod' => UnionPay::SIGNMETHOD_RSA,
			'txnType' => UnionPay::TXNTYPE_CONSUME,
			'txnSubType' => '01',
			'bizType' => '000201',
			'channelType' => '08',
			//商户信息
			'accessType' => '0',
			'merId' => $this->config['merId'],
			'frontUrl' => $this->config['notifyUrl'],
			'backUrl' => $this->config['returnUrl'],
			'frontFailUrl' => $this->config['frontFailUrl'],
			//订单信息
			'orderId' => $orderId, //商户订单号 不能含“-”或“_”
			'currencyCode' => '156',
			'txnAmt' => $txnAmt ,
			'txnTime' => date('YmdHis'),
			'reqReserved' => $reqReserved,
		];
		if(is_array($ext)) $params = array_merge($params,$ext);
		$params['signature'] = $this->sign($params);
		return $this->createPostForm($params);
	}

	public function onPayNotify($notifyData,callable $callback){
		parent::onPayNotify($notifyData,$callback);
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
		$ext['bizType'] = '000201';
		$ext['channelType'] = '08';
		$result = parent::payUndo($orderId,$origQryId,$txnAmt,$reqReserved,$reserved,$ext);
		return $result;
	}

	/**
	 * 消费撤销异步通知处理
	 * @param array $notifyData
	 * @param callable $callback
	 * @return mixed
	 * @throws \Exception
	 */
	public function onPayUndoNotify($notifyData,callable $callback){
		parent::onPayUndoNotify($notifyData,$callback);
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
		$ext['channelType'] = '08';
		$result = parent::refund($orderId,$origQryId,$refundAmt,$reqReserved,$reserved,$ext);
		return $result;
	}

	/**
	 * 退款异步通知处理
	 * @param array $notifyData
	 * @param callable $callback
	 * @return mixed
	 * @throws \Exception
	 */
	public function onRefundNotify($notifyData,callable $callback){
		parent::onRefundNotify($notifyData,$callback);
	}

	/**
	 * 组装报文
	 *
	 * @param array $params
	 * @return string
	 */
	function getRequestParamString($params) {
		$params_str = '';
		foreach ( $params as $key => $value ) {
			$params_str .= ($key . '=' . (!isset ( $value ) ? '' : urlencode( $value )) . '&');
		}
		return substr ( $params_str, 0, strlen ( $params_str ) - 1 );
	}

	/**
	 * 交易状态查询
	 * @ref https://open.unionpay.com/ajweb/product/newProApiShow?proId=1&apiId=66
	 * @param $orderId
	 * @param string $reserved
	 * @return mixed
	 */
	public function query($orderId,$reserved = ''){
		$params = array(
			'version' => '5.0.0',
			'encoding' => 'UTF-8',
			'certId' => $this->getSignCertId (),
			'signMethod' => UnionPay::SIGNMETHOD_RSA,
			'txnType' => '00',
			'txnSubType' => '00',
			'bizType' => '000000',
			'accessType' => '0',
			'orderId' => $orderId,
			'merId' =>  $this->config['merId'],
			'txnTime' => date('YmdHis'),
			'reserved' => $reserved,
		);
		$result = $this->post($params,self::URL_SINGLEQUERY);
		return $result;
	}

	/**
	 * 文件传输
	 * @ref https://open.unionpay.com/ajweb/product/newProApiShow?proId=1&apiId=72
	 * @param $settleDate
	 * @param string $fileType
	 * @return mixed
	 */
	public function fileDownload($settleDate,$fileType = '00'){
		$params = array(
			'version' => '5.0.0',
			'encoding' => 'UTF-8',
			'certId' => $this->getSignCertId (),
			'txnType' => UnionPay::TXNTYPE_FILEDOWNLOAD,
			'signMethod' => UnionPay::SIGNMETHOD_RSA,
			'txnSubType' => '01',
			'bizType' => '000000',
			'accessType' => '0',
			'merId' =>  $this->config['merId'],
			'settleDate' => $settleDate,//'0119', MMDD
			'txnTime' => date('YmdHis'),
			'fileType' => $fileType,
		);
		$params['signature'] = $this->sign($params);
		$result = $this->post($params,self::URL_FILEDOWNLOAD);
		return $result;
	}

	/**
	 * 预授权
	 * @ref https://open.unionpay.com/ajweb/product/newProApiShow?proId=1&apiId=68
	 * @param $orderId
	 * @param $amt
	 * @param $orderDesc
	 * @param string $reqReserved
	 * @return mixed
	 */
	public function preAuth($orderId,$amt,$orderDesc,$reqReserved = ''){
		$params = array(
			'version' => '5.1.0',
			'encoding' => 'UTF-8',
			'certId' => $this->getSignCertId (),
			'txnType' => UnionPay::TXNTYPE_PREAUTH,
			'txnSubType' => '01',
			'bizType' => '000201',
			'frontUrl' =>  $this->config['notifyUrl'],
			'backUrl' => $this->config['returnUrl'],
			'signMethod' => UnionPay::SIGNMETHOD_RSA,
			'channelType' => '08',		//渠道类型，07-PC，08-手机
			'accessType' => '0',
			'merId' => $this->config['merId'],
			'orderId' => $orderId,
			'txnTime' => date('YmdHis'),
			'txnAmt' => $amt,
			'currencyCode' => '156',
			'orderDesc' => $orderDesc,
			'reqReserved' => $reqReserved,
		);
		$params['signature'] = $this->sign($params);
		$result = $this->post($params,self::URL_BACKTRANS);
		return $result;
	}

	/**
	 * 预授权撤销
	 * @ref https://open.unionpay.com/ajweb/product/newProApiShow?proId=1&apiId=69
	 * @param $orderId
	 * @param $origQryId
	 * @param $txnAmt
	 * @param string $reqReserved
	 * @param string $reserved
	 * @return mixed
	 */
	public function preAuthUndo($orderId,$origQryId,$txnAmt,$reqReserved = '',$reserved = ''){
		$params = array(
			'version' => '5.1.0',
			'encoding' => 'UTF-8',
			'certId' => $this->getSignCertId (),
			'signMethod' => UnionPay::SIGNMETHOD_RSA,
			'txnType' => UnionPay::TXNTYPE_PREAUTHUNDO,
			'txnSubType' => '00',
			'bizType' => '000000',
			'accessType' => '0',
			'channelType' => '07',
			'orderId' => $orderId,
		 	'merId' =>  $this->config['merId'],
			'origQryId' => $origQryId,   //原预授权的queryId，可以从查询接口或者通知接口中获取
			'txnTime' => date('YmdHis'),
			'txnAmt' => $txnAmt,//交易金额，需和原预授权一致
			'backUrl' => $this->config['returnUrl'],
			'reserved' => $reserved,
			'reqReserved' => $reqReserved,
		);
		$params['signature'] = $this->sign($params);
		$result = $this->post($params,self::URL_BACKTRANS);
		return $result;
	}

	/**
	 * 预授权完成
	 * @ref https://open.unionpay.com/ajweb/product/newProApiShow?proId=1&apiId=70
	 * @param $orderId
	 * @param $origQryId
	 * @param $amt
	 * @param string $reqReserved
	 * @param string $reserved
	 * @return mixed
	 */
	public function preAuthFinish($orderId,$origQryId,$amt,$reqReserved = '',$reserved = ''){
		$params = array(
			'version' => '5.1.0',
			'encoding' => 'UTF-8',
			'certId' => $this->getSignCertId (),
			'signMethod' => UnionPay::SIGNMETHOD_RSA,
			'txnType' => UnionPay::TXNTYPE_PREAUTHFINISH,
			'txnSubType' => '00',
			'bizType' => '000201',
			'accessType' => '0',
			'channelType' => '07',
			'orderId' => $orderId,//商户订单号，重新产生，不同于原消费
			'merId' =>  $this->config['merId'],
			'origQryId' => $origQryId, //原预授权的queryId，可以从查询接口或者通知接口中获取
			'txnTime' => date('YmdHis'),
			'txnAmt' => $amt,
			'backUrl' => $this->config['returnUrl'],
			'reserved' => $reserved,
			'reqReserved' => $reqReserved,
		);
		$params['signature'] = $this->sign($params);
		$result = $this->post($params,self::URL_BACKTRANS);
		return $result;
	}

	/**
	 * 预授权完成撤销
	 * @ref https://open.unionpay.com/ajweb/product/newProApiShow?proId=1&apiId=71
	 * @param $orderId
	 * @param $origQryId
	 * @param $txnAmt
	 * @param string $reqReserved
	 * @param string $reserved
	 * @return mixed
	 */
	public function preAuthFinishUndo($orderId,$origQryId,$txnAmt,$reqReserved = '',$reserved = ''){
		$params = array(
			'version' => '5.1.0',
			'encoding' => 'UTF-8',
			'certId' => $this->getSignCertId (),
			'signMethod' => UnionPay::SIGNMETHOD_RSA,
			'txnType' => UnionPay::TXNTYPE_PREAUTHFINISHUNDO,
			'txnSubType' => '00',
			'bizType' => '000000',
			'accessType' => '0',
			'channelType' => '07',
			'orderId' => $orderId,
			'merId' => $this->config['merId'],
			'origQryId' => $origQryId,
			'txnTime' => date('YmdHis'),
			'txnAmt' => $txnAmt,
			'backUrl' => $this->config['returnUrl'],
			'reserved' => $reserved,
			'reqReserved' => $reqReserved,
		);
		$params['signature'] = $this->sign($params);
		$result = $this->post($params,self::URL_BACKTRANS);
		return $result;
	}

	/**
	 * 验证签名
	 * 验签规则：
	 * 除signature域之外的所有项目都必须参加验签
	 * 根据key值按照字典排序，然后用&拼接key=value形式待验签字符串；
	 * 然后对待验签字符串使用sha1算法做摘要；
	 * 用银联公钥对摘要和签名信息做验签操作
	 *
	 * @throws \Exception
	 * @return bool
	 */
	public function verifySign(){
		$publicKey = $this->getVerifyPublicKey();
		$verifyArr = $this->filterBeforSign();
		ksort($verifyArr);
		$verifyStr = $this->arrayToString($verifyArr);
		$verifySha1 = sha1($verifyStr);
		$signature = base64_decode($this->params['signature']);
		$result = openssl_verify($verifySha1, $signature, $publicKey);
		if($result === -1) {
			throw new \Exception('Verify Error:'.openssl_error_string());
		}
		return $result === 1 ? true : false;
	}

	public function validateSign($params){
		$publicKey = $this->getVerifyPublicKey();
		$verifyArr = clone $params;
		unset($verifyArr['signature']);
		ksort($verifyArr);
		$verifyStr = $this->arrayToString($verifyArr);
		$verifySha1 = sha1($verifyStr);
		$signature = base64_decode($params['signature']);
		$result = openssl_verify($verifySha1, $signature, $publicKey);
		if($result === -1) {
			throw new \Exception('Verify Error:'.openssl_error_string());
		}
		return $result === 1 ? true : false;
	}

	/**
	 * 加密公钥更新查询
	 * @param $orderId
	 * @param string $reqReserved
	 * @param string $reserved
	 * @param array $ext
	 * @return mixed
	 */
	public function updatePublicKey($orderId,$reqReserved = '',$reserved = '',$ext = []){
		$params = array(
			'version' => '5.1.0',
			'encoding' => 'UTF-8',
			'bizType' => '000000',
			'txnTime' => date('YmdHis'),
			'certId' => $this->getSignCertId (),
			'signMethod' => UnionPay::SIGNMETHOD_RSA,
			'txnType' => UnionPay::TXNTYPE_UPDATEPUBLICKEY,
			'txnSubType' => '00',
			'accessType' => '0',
			'channelType' => '07',
			'orderId' => $orderId,
			'merId' =>  $this->config['merId'],
			'certType' => '01', //原预授权的queryId，可以从查询接口或者通知接口中获取
			'reserved' => $reserved,
			'reqReserved' => $reqReserved,
		);
		$params['signature'] = $this->sign($params);
		$result = $this->post($params,self::URL_BACKTRANS);
		return $result;
	}
}