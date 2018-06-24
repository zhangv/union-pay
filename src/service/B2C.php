<?php
namespace zhangv\unionpay\service;
use zhangv\unionpay\UnionPay;

/**
 * 网关支付
 * @license MIT
 * @author zhangv
 * @ref https://open.unionpay.com/ajweb/product/newProApiList?proId=1
 * */
class B2C extends UnionPay{

	/**
	 * 支付
	 * @param $orderId
	 * @param $txnAmt
	 * @param array $ext
	 * @return string
	 */
	public function pay($orderId,$txnAmt,$ext = []){
		$params = [
			'version' => $this->config['version'],
			'encoding' => $this->config['encoding'],
			'signMethod' => UnionPay::SIGNMETHOD_RSA,
			'txnType' => UnionPay::TXNTYPE_CONSUME,
			'txnSubType' => '01',
			'bizType' => UnionPay::BIZTYPE_B2C,
			'channelType' => '07',
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
		return $this->createPostForm($params);
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
		$params = [
			'version' => $this->config['version'],
			'encoding' => $this->config['encoding'],
			'bizType' => UnionPay::BIZTYPE_B2C,
			'txnTime' => date('YmdHis'),
			'backUrl' => $this->config['notifyUrl'],
			'txnAmt' => $txnAmt,
			'txnType' => UnionPay::TXNTYPE_CONSUMEUNDO,
			'txnSubType' => '00',
			'accessType' => '0',
			'signMethod' => UnionPay::SIGNMETHOD_RSA,
			'channelType' => '07',
			'merId' => $this->config['merId'],
			'orderId' => $orderId,
			'origQryId' => $origQryId,
		];
		$params['certId'] =  $this->getSignCertId();
		$params = array_merge($params,$ext);
		$params['signature'] = $this->sign($params);
		$result = $this->post($params,$this->backTransUrl);
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
		if($this->validateSign($notifyData)){
			if($callback && is_callable($callback)){
				$queryId = $notifyData['queryId'];
				return call_user_func_array( $callback , [$notifyData] );
			}else{
				print('ok');
			}
		}else{
			throw new \Exception('Invalid paid notify data');
		}
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
		$params = [
			'version' => $this->config['version'],
			'encoding' => $this->config['encoding'],
			'signMethod' => UnionPay::SIGNMETHOD_RSA,
			'txnType' => UnionPay::TXNTYPE_REFUND,
			'txnSubType' => '00',
			'bizType' => UnionPay::BIZTYPE_B2C,
			'accessType' => '0',
			'channelType' => '07',
			'orderId' => $orderId,
			'merId' => $this->config['merId'],
			'origQryId' => $origQryId,
			'txnTime' => date('YmdHis'),
			'txnAmt' => $refundAmt,
			'backUrl' => $this->config['returnUrl'],
		];
		$params['certId'] =  $this->getSignCertId();
		$params = array_merge($params,$ext);
		$params['signature'] = $this->sign($params);
		$result = $this->post($params,$this->backTransUrl);
		return $result;
	}

	/**
	 * 交易状态查询
	 * @param string $orderId
	 * @param string $txnTime
	 * @param array $ext
	 * @return mixed
	 */
	public function query($orderId,$txnTime,$ext = []){
		$params = array(
			'version' => $this->config['version'],
			'encoding' => $this->config['encoding'],
			'signMethod' => UnionPay::SIGNMETHOD_RSA,
			'txnType' => '00',
			'txnSubType' => '00',
			'bizType' => '000000',
			'accessType' => '0',
			'orderId' => $orderId,
			'merId' =>  $this->config['merId'],
			'txnTime' => $txnTime
		);
		$params['certId'] =  $this->getSignCertId();
		$params = array_merge($params,$ext);
		$params['signature'] = $this->sign($params);
		$result = $this->post($params,$this->singleQueryUrl,false);
		return $result;
	}

	/**
	 * 文件传输
	 * @param string $settleDate MMDD
	 * @param string $fileType
	 * @return mixed
	 */
	public function fileDownload($settleDate,$fileType = '00'){
		$params = array(
			'version' => $this->config['version'],
			'encoding' => $this->config['encoding'],
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
		$params['certId'] =  $this->getSignCertId();
		$params['signature'] = $this->sign($params);
		$result = $this->post($params,$this->fileDownloadUrl,false);
		return $result;
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
			'channelType' => '07',
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
		$result = $this->createPostForm($params,'预授权');
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
		$params = array(
			'version' => $this->config['version'],
			'encoding' => $this->config['encoding'],
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
			'backUrl' => $this->config['notifyUrl'],
		);
		$params['certId'] =  $this->getSignCertId();
		$params = array_merge($params,$ext);
		$params['signature'] = $this->sign($params);
		$result = $this->post($params,$this->backTransUrl);
		return $result;
	}

	/**
	 * 预授权完成
	 * @param $orderId
	 * @param $origQryId
	 * @param $amt
	 * @param array $ext
	 * @return array
	 */
	public function preAuthFinish($orderId,$origQryId,$amt,$ext = []){
		$params = array(
			'version' => $this->config['version'],
			'encoding' => $this->config['encoding'],
			'signMethod' => UnionPay::SIGNMETHOD_RSA,
			'txnType' => UnionPay::TXNTYPE_PREAUTHFINISH,
			'txnSubType' => '00',
			'bizType' => UnionPay::BIZTYPE_B2C,
			'accessType' => '0',
			'channelType' => '07',
			'orderId' => $orderId,//商户订单号，重新产生，不同于原消费
			'merId' =>  $this->config['merId'],
			'origQryId' => $origQryId, //原预授权的queryId，可以从查询接口或者通知接口中获取
			'txnTime' => date('YmdHis'),
			'txnAmt' => $amt,
			'backUrl' => $this->config['notifyUrl'],
		);
		$params['certId'] =  $this->getSignCertId();
		$params = array_merge($params,$ext);
		$params['signature'] = $this->sign($params);
		$result = $this->post($params,$this->backTransUrl);
		return $result;
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
		$params = array(
			'version' => $this->config['version'],
			'encoding' => $this->config['encoding'],
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
			'backUrl' => $this->config['notifyUrl'],
		);
		$params['certId'] =  $this->getSignCertId();
		$params = array_merge($params,$ext);
		$params['signature'] = $this->sign($params);
		$result = $this->post($params,$this->backTransUrl);
		return $result;
	}

	/**
	 * 加密公钥更新查询
	 * @param $orderId
	 * @param array $ext
	 * @return mixed
	 */
	public function updatePublicKey($orderId,$ext = []){
		$params = array(
			'version' => $this->config['version'],
			'encoding' => $this->config['encoding'],
			'bizType' => '000000',
			'txnTime' => date('YmdHis'),
			'signMethod' => UnionPay::SIGNMETHOD_RSA,
			'txnType' => UnionPay::TXNTYPE_UPDATEPUBLICKEY,
			'txnSubType' => '00',
			'accessType' => '0',
			'channelType' => '07',
			'orderId' => $orderId,
			'merId' =>  $this->config['merId'],
			'certType' => '01',
		);
		$params['certId'] =  $this->getSignCertId();
		$params = array_merge($params,$ext);
		$params['signature'] = $this->sign($params);
		$result = $this->post($params,$this->backTransUrl);
		return $result;
	}

}