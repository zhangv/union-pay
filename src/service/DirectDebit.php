<?php
namespace zhangv\unionpay\service;
use zhangv\unionpay\UnionPay;

use \Exception;
/**
 * 代收
 * @license MIT
 * @author zhangv
 * @ref https://open.unionpay.com/ajweb/product/newProApiList?proId=68
 * */
class DirectDebit extends B2C {

	/**
	 * 前台授权代收协议
	 * @param string $orderId
	 * @param string $accNo
	 * @param array $customerInfo
	 * @param array $ext
	 * @return string
	 */
	public function authorize($orderId,$accNo,$customerInfo,$ext = []){
		$params = [
			'version' => $this->config['version'],
			'encoding' => $this->config['encoding'],
			'signMethod' => UnionPay::SIGNMETHOD_RSA,
			'txnType' => '72',
			'txnSubType' => '11',
			'bizType' => '000501',
			'channelType' => '07',
			'frontUrl' => $this->config['returnUrl'],
			'backUrl' => $this->config['notifyUrl'],
			'accessType' => '0', //接入类型
			'merId' => $this->config['merId'],
			'orderId' => $orderId,
			'txnTime' => date('YmdHis'),
			'encryptCertId' => $this->getCertIdCer($this->config['encryptCertPath']),
		];
		$params['accNo'] = $accNo;
		$params['customerInfo'] = $this->getCustomerInfo($customerInfo);
		$params['certId'] =  $this->getSignCertId();
		$params = array_merge($params,$ext);
		$params['signature'] = $this->sign($params);
		return $this->createPostForm($params);
	}

	/**
	 * 后台授权代收协议
	 * @param string $orderId
	 * @param string $accNo
	 * @param array $customerInfo
	 * @param array $ext
	 * @return array
	 */
	public function backAuthorize($orderId,$accNo,$customerInfo,$ext = []){
		$params = [
			'version' => $this->config['version'],
			'encoding' => $this->config['encoding'],
			'signMethod' => UnionPay::SIGNMETHOD_RSA,
			'txnType' => '72',
			'txnSubType' => '11',
			'bizType' => '000501',
			'channelType' => '07',
			'accessType' => '0', //接入类型
			'merId' => $this->config['merId'],
			'orderId' => $orderId,
			'txnTime' => date('YmdHis'),
			'encryptCertId' => $this->getCertIdCer($this->config['encryptCertPath']),
		];
		$params['accNo'] = $this->encryptData($accNo);
		$params['customerInfo'] = $this->encryptCustomerInfo($customerInfo);
		$params['certId'] =  $this->getSignCertId();
		$params = array_merge($params,$ext);
		$params['signature'] = $this->sign($params);
		return $this->post($params,$this->backTransUrl);
	}

	/**
	 * 解除授权代收协议
	 * @param string $orderId
	 * @param string $accNo
	 * @param array $ext
	 * @return array
	 */
	public function unauthorize($orderId,$accNo,$ext = []){
		$params = [
			'version' => $this->config['version'],
			'encoding' => $this->config['encoding'],
			'signMethod' => UnionPay::SIGNMETHOD_RSA,
			'txnType' => '74',
			'txnSubType' => '04',
			'bizType' => '000501',
			'channelType' => '07',
			'accessType' => '0', //接入类型
			'merId' => $this->config['merId'],
			'orderId' => $orderId,
			'txnTime' => date('YmdHis'),
			'encryptCertId' => $this->getCertIdCer($this->config['encryptCertPath']),
		];
		$params['accNo'] = $this->encryptData($accNo);
		$params['certId'] =  $this->getSignCertId();
		$params = array_merge($params,$ext);
		$params['signature'] = $this->sign($params);
		return $this->post($params,$this->backTransUrl);
	}

	/**
	 * 代收
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
			'txnType' => '11',
			'txnSubType' => '00',
			'bizType' => UnionPay::BIZTYPE_DIRECTDEBIT,
			'channelType' => '07',
			'frontUrl' => $this->config['returnUrl'],
			'backUrl' => $this->config['notifyUrl'],
			'accessType' => '0', //接入类型
			'merId' => $this->config['merId'],
			'orderId' => $orderId,
			'txnTime' => date('YmdHis'),
			'txnAmt' => $txnAmt ,
			'currencyCode' => '156',
			'encryptCertId' => $this->getCertIdCer($this->config['encryptCertPath']),
		];
		$params['certId'] =  $this->getSignCertId();
		$params = array_merge($params,$ext);
		$params['signature'] = $this->sign($params);
		return $this->createPostForm($params);
	}

	public function onPayNotify($notifyData,callable $callback){
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
	 * 代收(使用绑定标识号)
	 * @param string $orderId
	 * @param string $txnAmt
	 * @param string $bindId
	 * @param array $ext
	 * @return string
	 */
	public function payByBindId($orderId,$txnAmt,$bindId,$ext = []){
		$params = [
			'version' => $this->config['version'],
			'encoding' => $this->config['encoding'],
			'signMethod' => UnionPay::SIGNMETHOD_RSA,
			'txnType' => '11',
			'txnSubType' => '00',
			'bizType' => UnionPay::BIZTYPE_DIRECTDEBIT,
			'channelType' => '07',
			'backUrl' => $this->config['notifyUrl'],
			'accessType' => '0', //接入类型
			'merId' => $this->config['merId'],
			'orderId' => $orderId,
			'txnTime' => date('YmdHis'),
			'txnAmt' => $txnAmt ,
			'currencyCode' => '156',
			'encryptCertId' => $this->getCertIdCer($this->config['encryptCertPath']),
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
		$ext['bizType'] = UnionPay::BIZTYPE_DIRECTDEBIT;
		return parent::payUndo($orderId,$origQryId,$txnAmt,$ext);
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
		$ext['bizType'] = UnionPay::BIZTYPE_DIRECTDEBIT;
		return parent::refund($orderId,$origQryId,$refundAmt,$ext);
	}

	/**
	 * 退款异步通知处理
	 * @param array $notifyData
	 * @param callable $callback
	 * @return mixed
	 * @throws \Exception
	 */
	public function onRefundNotify($notifyData,callable $callback){
		if($this->validateSign($notifyData)){
			if($callback && is_callable($callback)){
				return call_user_func_array( $callback , [$notifyData] );
			}else{
				print('ok');
			}
		}else{
			throw new \Exception('Invalid paid notify data');
		}
	}

	/**
	 * 交易状态查询
	 * @param string $orderId
	 * @param string $txnTime
	 * @param array $ext
	 * @return mixed
	 */
	public function query($orderId,$txnTime,$ext = []){
		return parent::query($orderId,$txnTime,$ext);
	}

	/**
	 * 后台绑定
	 * @param $orderId
	 * @param $accNo
	 * @param $customerInfo
	 * @param $bindId
	 * @param array $ext
	 * @return array
	 */
	public function backBind($orderId,$accNo,$customerInfo,$bindId,$ext = []){
		$params = [
			'version' => $this->config['version'],
			'encoding' => $this->config['encoding'],
			'signMethod' => UnionPay::SIGNMETHOD_RSA,
			'txnType' => '72',
			'txnSubType' => '01',
			'bizType' => UnionPay::BIZTYPE_DIRECTDEBIT,
			'channelType' => '07',
			'accessType' => '0', //接入类型
			'merId' => $this->config['merId'],
			'orderId' => $orderId,
			'txnTime' => date('YmdHis'),
			'currencyCode' => '156',
			'encryptCertId' => $this->getCertIdCer($this->config['encryptCertPath']),
		];
		$params['accNo'] = $this->encryptData($accNo);
		$params['customerInfo'] = $this->encryptCustomerInfo($customerInfo);
		$params['bindId'] = $bindId;
		$params['certId'] =  $this->getSignCertId();
		$params = array_merge($params,$ext);
		$params['signature'] = $this->sign($params);
		return $this->post($params,$this->backTransUrl);
	}

	/**
	 * 前台绑定
	 * @param $orderId
	 * @param $accNo
	 * @param $customerInfo
	 * @param $bindId
	 * @param array $ext
	 * @return string
	 */
	public function frontBind($orderId,$accNo,$customerInfo,$bindId,$ext = []){
		$params = [
			'version' => $this->config['version'],
			'encoding' => $this->config['encoding'],
			'signMethod' => UnionPay::SIGNMETHOD_RSA,
			'txnType' => '72',
			'txnSubType' => '01',
			'bizType' => UnionPay::BIZTYPE_DIRECTDEBIT,
			'channelType' => '07',
			'frontUrl' => $this->config['returnUrl'],
			'backUrl' => $this->config['notifyUrl'],
			'accessType' => '0', //接入类型
			'merId' => $this->config['merId'],
			'orderId' => $orderId,
			'txnTime' => date('YmdHis'),
			'currencyCode' => '156',
			'encryptCertId' => $this->getCertIdCer($this->config['encryptCertPath']),
		];
		$params['accNo'] = $this->encryptData($accNo);
		$params['customerInfo'] = $this->encryptCustomerInfo($customerInfo);
		$params['bindId'] = $bindId;
		$params['certId'] =  $this->getSignCertId();
		$params = array_merge($params,$ext);
		$params['signature'] = $this->sign($params);
		return $this->createPostForm($params,'绑定');
	}

	/**
	 * 查询绑定
	 * @param $orderId
	 * @param $bindId
	 * @param array $ext
	 * @return array
	 */
	public function queryBind($orderId,$bindId,$ext = []){
		$params = [
			'version' => $this->config['version'],
			'encoding' => $this->config['encoding'],
			'signMethod' => UnionPay::SIGNMETHOD_RSA,
			'txnType' => '75',
			'txnSubType' => '00',
			'bizType' => UnionPay::BIZTYPE_DIRECTDEBIT,
			'channelType' => '07',
			'accessType' => '0', //接入类型
			'merId' => $this->config['merId'],
			'orderId' => $orderId,
			'txnTime' => date('YmdHis'),
			'currencyCode' => '156',
			'encryptCertId' => $this->getCertIdCer($this->config['encryptCertPath']),
		];
		$params['bindId'] = $bindId;
		$params['certId'] =  $this->getSignCertId();
		$params = array_merge($params,$ext);
		$params['signature'] = $this->sign($params);
		return $this->post($params,$this->backTransUrl);
	}

	/**
	 * 解除绑定
	 * @param $orderId
	 * @param $bindId
	 * @param array $ext
	 * @return array
	 */
	public function removeBind($orderId,$bindId,$ext = []){
		$params = [
			'version' => $this->config['version'],
			'encoding' => $this->config['encoding'],
			'signMethod' => UnionPay::SIGNMETHOD_RSA,
			'txnType' => '74',
			'txnSubType' => '00',
			'bizType' => UnionPay::BIZTYPE_DIRECTDEBIT,
			'channelType' => '07',
			'accessType' => '0', //接入类型
			'merId' => $this->config['merId'],
			'orderId' => $orderId,
			'txnTime' => date('YmdHis'),
			'currencyCode' => '156',
			'encryptCertId' => $this->getCertIdCer($this->config['encryptCertPath']),
		];
		$params['bindId'] = $bindId;
		$params['certId'] =  $this->getSignCertId();
		$params = array_merge($params,$ext);
		$params['signature'] = $this->sign($params);
		return $this->post($params,$this->backTransUrl);
	}

	/**
	 * 后台认证
	 * @param string $orderId
	 * @param string $accNo
	 * @param array $customerInfo
	 * @param array $ext
	 * @return array
	 */
	public function backAuthenticate($orderId,$accNo,$customerInfo,$ext = []){
		$params = [
			'version' => $this->config['version'],
			'encoding' => $this->config['encoding'],
			'signMethod' => UnionPay::SIGNMETHOD_RSA,
			'txnType' => '72',
			'txnSubType' => '01',
			'bizType' => UnionPay::BIZTYPE_DIRECTDEBIT,
			'channelType' => '07',
			'accessType' => '0', //接入类型
			'merId' => $this->config['merId'],
			'orderId' => $orderId,
			'txnTime' => date('YmdHis'),
			'currencyCode' => '156',
			'encryptCertId' => $this->getCertIdCer($this->config['encryptCertPath']),
		];
		$params['accNo'] = $this->encryptData($accNo);
		$params['customerInfo'] = $this->encryptCustomerInfo($customerInfo);
		$params['certId'] =  $this->getSignCertId();
		$params = array_merge($params,$ext);
		$params['signature'] = $this->sign($params);
		return $this->post($params,$this->backTransUrl);
	}

	/**
	 * 前台认证
	 * @param string $orderId
	 * @param string $accNo
	 * @param array $customerInfo
	 * @param array $ext
	 * @return string
	 */
	public function frontAuthenticate($orderId,$accNo,$customerInfo,$ext = []){
		$params = [
			'version' => $this->config['version'],
			'encoding' => $this->config['encoding'],
			'signMethod' => UnionPay::SIGNMETHOD_RSA,
			'txnType' => '72',
			'txnSubType' => '10',
			'bizType' => UnionPay::BIZTYPE_DIRECTDEBIT,
			'channelType' => '07',
			'accessType' => '0', //接入类型
			'merId' => $this->config['merId'],
			'orderId' => $orderId,
			'txnTime' => date('YmdHis'),
			'currencyCode' => '156',
			'encryptCertId' => $this->getCertIdCer($this->config['encryptCertPath']),
			'reserved' =>'{checkFlag=11100}'
		];
		$params['accNo'] = $this->encryptData($accNo);
		$params['customerInfo'] = $this->encryptCustomerInfo($customerInfo);
		$params['certId'] =  $this->getSignCertId();
		$params = array_merge($params,$ext);
		$params['signature'] = $this->sign($params);
		return $this->createPostForm($params,'认证');
	}

	/**
	 * 短信认证
	 * @param string $orderId
	 * @param string $accNo
	 * @param array $customerInfo
	 * @param array $ext
	 * @return array
	 */
	public function smsAuthenticate($orderId,$accNo,$customerInfo,$ext = []){
		$params = [
			'version' => $this->config['version'],
			'encoding' => $this->config['encoding'],
			'signMethod' => UnionPay::SIGNMETHOD_RSA,
			'txnType' => '77',
			'txnSubType' => '01',
			'bizType' => UnionPay::BIZTYPE_DIRECTDEBIT,
			'channelType' => '07',
			'accessType' => '0', //接入类型
			'merId' => $this->config['merId'],
			'orderId' => $orderId,
			'txnTime' => date('YmdHis'),
			'currencyCode' => '156',
		];
		$params['accNo'] = $this->encryptData($accNo);
		$params['customerInfo'] = $this->encryptCustomerInfo($customerInfo);
		$params['certId'] =  $this->getSignCertId();
		$params = array_merge($params,$ext);
		$params['signature'] = $this->sign($params);
		return $this->post($params,$this->backTransUrl);
	}

	/**
	 * 批量代收
	 * @param string $orderId
	 * @param string $batchNo
	 * @param array $totalQty
	 * @param array $totalAmt
	 * @param array $filePath
	 * @param array $ext
	 * @throws Exception
	 * @return array
	 */
	public function batchPay($orderId,$batchNo,$totalQty,$totalAmt,$filePath,$ext = []){
		if(!file_exists($filePath)) throw new Exception("File path does not exists - $filePath");
		$params = [
			'version' => $this->config['version'],
			'encoding' => $this->config['encoding'],
			'signMethod' => UnionPay::SIGNMETHOD_RSA,
			'txnType' => '21',
			'txnSubType' => '02',
			'bizType' => UnionPay::BIZTYPE_DIRECTDEBIT,
			'channelType' => '07',
			'accessType' => '0', //接入类型
			'merId' => $this->config['merId'],
			'orderId' => $orderId,
			'txnTime' => date('YmdHis'),
			'currencyCode' => '156',
		];
		$params['batchNo'] = $batchNo;
		$params['totalQty'] = $totalQty;
		$params['totalAmt'] = $totalAmt;
		$params['fileContent'] = $this->encodeFileContent($filePath);
		$params['certId'] =  $this->getSignCertId();
		$params = array_merge($params,$ext);
		$params['signature'] = $this->sign($params);
		return $this->post($params,$this->batchTransUrl);
	}

	/**
	 * 查询批量支付交易
	 * @param string $batchNo
	 * @param array $ext
	 * @throws Exception
	 * @return array
	 */
	public function queryBatch($batchNo,$ext = []){
		$params = [
			'version' => $this->config['version'],
			'encoding' => $this->config['encoding'],
			'signMethod' => UnionPay::SIGNMETHOD_RSA,
			'txnType' => '22',
			'txnSubType' => '02',
			'bizType' => UnionPay::BIZTYPE_DIRECTDEBIT,
			'channelType' => '07',
			'accessType' => '0', //接入类型
			'merId' => $this->config['merId'],
			'txnTime' => date('YmdHis'),
		];
		$params['batchNo'] = $batchNo;
		$params['certId'] =  $this->getSignCertId();
		$params = array_merge($params,$ext);
		$params['signature'] = $this->sign($params);
		return $this->post($params,$this->batchTransUrl);
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

}