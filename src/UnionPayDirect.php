<?php
namespace zhangv\unionpay;

/**
 * 银联无跳转支付(标准版)
 * @license MIT
 * @author zhangv
 * @ref https://open.unionpay.com/ajweb/product/newProDetail?proId=2&cataId=20
 * */
class UnionPayDirect extends UnionPay {
	const SMSTYPE_OPEN = '00', SMSTYPE_PAY = '02',SMSTYPE_PREAUTH = '04',SMSTYPE_OTHER = '05';

	/**
	 * 后台开通（需要用申请的商户号，并授权后方可测试）
	 * @param $orderId
	 * @param $accNo
	 * @param $customerInfo
	 * @param null $ext
	 * @return mixed
	 */
	public function backOpen($orderId,$accNo,$customerInfo,$ext = null):array{
		$params = array(
			'version' => $this->config['version'],
			'signMethod' =>  UnionPay::SIGNMETHOD_RSA,
			'encoding' => 'UTF-8',
			'txnType' => '79',
			'txnSubType' => '00',
			'bizType' => UnionPay::BIZTYPE_DIRECT,
			'accessType' => UnionPay::ACCESSTYPE_MERCHANT,
			'channelType' => '07',
			'encryptCertId' => $this->getCertIdCer($this->config['encryptCertPath']),
		);
		$params['merId' ] =  $this->config['merId'];
		$params['orderId'] =  $orderId;
		$params['txnTime'] = date('YmdHis');
		$params['accNo'] =  $this->encryptData($accNo);
		$params['customerInfo'] =  $this->encryptCustomerInfo($customerInfo);
		$params['certId'] =  $this->getSignCertId();
		$params['signature'] = $this->sign($params);
		$result = $this->post($params,$this->backTransUrl);
		return $result;
	}

	/**
	 * 前台开通
	 * @param $orderId
	 * @param $accNo
	 * @param $customerInfo
	 * @param null $ext
	 * @return string
	 */
	public function frontOpen($orderId,$accNo,$customerInfo,$ext = null){
		$params = array(
			'version' => $this->config['version'],
			'signMethod' =>  UnionPay::SIGNMETHOD_RSA,
			'encoding' => 'utf-8',
			'txnType' => '79',
			'txnSubType' => '00',
			'bizType' => UnionPay::BIZTYPE_DIRECT,
			'accessType' => UnionPay::ACCESSTYPE_MERCHANT,
			'channelType' => '07',
			'encryptCertId' => $this->getCertIdCer($this->config['encryptCertPath']),
		);
		$params['merId' ] =  $this->config['merId'];
		$params['orderId'] =  $orderId;
		$params['txnTime'] = date('YmdHis');
		$params['accNo'] =  $this->encryptData($accNo);
		$params['customerInfo'] =  $this->encryptCustomerInfo($customerInfo);

		$params['certId'] =  $this->getSignCertId();
		$params['accType'] = '01';
		$params['frontUrl'] = $this->config['openReturnUrl'];
		$params['backUrl'] = $this->config['openNotifyUrl'];
		$params['payTimeout'] = '';// date('YmdHis', strtotime('+15 minutes')); //问了银联技术支持，让留空，否则测试时会报错：订单已超时

		$params['signature'] = $this->sign($params);
		$result = $this->createPostForm($params,'开通');
		return $result;
	}

	public function onOpenNotify($notifyData,callable $callback){
		if($this->validateSign($notifyData)){
			if($callback && is_callable($callback)){
				return call_user_func_array( $callback , [$notifyData] );
			}else{
				print('ok');
			}
		}else{
			throw new \Exception('Invalid opened notify data');
		}
	}

	/**
	 * 查询开通
	 * @param $orderId
	 * @param $accNo
	 * @return array
	 */
	public function queryOpen($orderId,$accNo):array{
		$params = array(
			'version' => $this->config['version'],
			'signMethod' =>  UnionPay::SIGNMETHOD_RSA,
			'encoding' => 'UTF-8',
			'txnType' => '78',
			'txnSubType' => '00',
			'bizType' => UnionPay::BIZTYPE_DIRECT,
			'accessType' => UnionPay::ACCESSTYPE_MERCHANT,
			'channelType' => '07',
			'encryptCertId' => $this->getCertIdCer($this->config['encryptCertPath']),
		);
		$params['merId' ] =  $this->config['merId'];
		$params['orderId'] =  $orderId;
		$params['txnTime'] = date('YmdHis');
		$params['accNo'] =  $this->encryptData($accNo);
		$params['certId'] =  $this->getSignCertId();
		$params['signature'] = $this->sign($params);
		$result = $this->post($params,$this->backTransUrl);
		return $result;
	}

	/**
	 * 发送短信验证码(开通、支付、预授权...)
	 * @ref https://open.unionpay.com/ajweb/product/newProApiShow?proId=2&apiId=93
	 * @param $orderId
	 * @param $accNo
	 * @param $customerInfo
	 * @param $smsType
	 * @return array
	 */
	public function sms($orderId,$accNo,$customerInfo,$smsType = UnionPayDirect::SMSTYPE_OPEN):array{
		$params = array(
			'version' => $this->config['version'],
			'signMethod' =>  UnionPay::SIGNMETHOD_RSA,
			'encoding' => 'UTF-8',
			'txnType' => '77',
			'txnSubType' => $smsType,
			'bizType' => UnionPay::BIZTYPE_DIRECT,
			'accessType' => UnionPay::ACCESSTYPE_MERCHANT,
			'channelType' => '07',
			'encryptCertId' => $this->getCertIdCer($this->config['encryptCertPath']),
		);
		$params['merId' ] =  $this->config['merId'];
		$params['orderId'] =  $orderId;
		$params['txnTime'] = date('YmdHis');
		$params['accNo'] =  $this->encryptData($accNo);
		$params['customerInfo'] =  $this->encryptCustomerInfo($customerInfo);
		$params['certId'] =  $this->getSignCertId();
		$params['signature'] = $this->sign($params);
		$result = $this->post($params,$this->backTransUrl);
		return $result;
	}


	/**
	 * 支付
	 * @param $orderId
	 * @param $txnAmt
	 * @param string $reqReserved
	 * @param string $reserved
	 * @param array $ext
	 * @return string
	 */
	public function pay($orderId,$txnAmt,$reqReserved = '',$reserved = '',$ext = []){
		$params = array(
			'version' => $this->config['version'],
			'signMethod' =>  UnionPay::SIGNMETHOD_RSA,
			'encoding' => 'utf-8',
			'txnType' => '01',
			'txnSubType' => '01', //01 - 自助消费  03 - 分期付款
			'bizType' => UnionPay::BIZTYPE_DIRECT,
			'accessType' => UnionPay::ACCESSTYPE_MERCHANT,
			'channelType' => '07',
			'currencyCode' => '156',          //交易币种，境内商户勿改
			'encryptCertId' => $this->getCertIdCer($this->config['encryptCertPath']),
			'backUrl' => $this->config['notifyUrl']
		);
		$params['merId' ] =  $this->config['merId'];
		$params['orderId'] =  $orderId;
		$params['txnTime'] = date('YmdHis');
		$params['txnAmt'] = $txnAmt;
		$accNo = $ext['accNo'];
		$params['accNo'] = $this->encryptData($accNo);
		$customerInfo = $ext['customerInfo'];
		$params['customerInfo'] =  $this->encryptCustomerInfo($customerInfo);
		$params['certId'] =  $this->getSignCertId();
		$params['signature'] = $this->sign($params);
		$result = $this->post($params,$this->backTransUrl);
		return $result;
	}

	/**
	 * 分期付款
	 * @param $orderId
	 * @param $txnAmt
	 * @param string $reqReserved
	 * @param string $reserved
	 * @param array $ext
	 * @return string
	 */
	public function payByInstallment($orderId,$txnAmt,$reqReserved = '',$reserved = '',$ext = []){
		$params = array(
			'version' => $this->config['version'],
			'signMethod' =>  UnionPay::SIGNMETHOD_RSA,
			'encoding' => 'UTF-8',
			'txnType' => '01',
			'txnSubType' => '03',
			'bizType' => UnionPay::BIZTYPE_DIRECT,
			'accessType' => UnionPay::ACCESSTYPE_MERCHANT,
			'channelType' => '07',
			'currencyCode' => '156',          //交易币种，境内商户勿改
			'encryptCertId' => $this->getCertIdCer($this->config['encryptCertPath']),
		);
		$params['backUrl' ] = $this->config['notifyUrl'];
		$params['merId' ] =  $this->config['merId'];
		$params['orderId'] =  $orderId;
		$params['txnAmt'] = $txnAmt;
		$params['txnTime'] = date('YmdHis');
		$accNo = $ext['accNo'];
		$params['accNo'] =  $this->encryptData($accNo);
		$customerInfo = $ext['customerInfo'];
		$params['customerInfo'] =  $this->encryptCustomerInfo($customerInfo);
		$params['certId'] =  $this->getSignCertId();

		//分期付款用法（商户自行设计分期付款展示界面）：
		//【生产环境】支持的银行列表清单请联系银联业务运营接口人索要
 		$params['instalTransInfo'] = $ext['instalTransInfo'];

		$params['signature'] = $this->sign($params);
		$result = $this->post($params,$this->backTransUrl);
		return $result;
	}

	/**
	 * 前台开通并支付
	 * @param $orderId
	 * @param $accNo
	 * @param $customerInfo
	 * @param null $ext
	 * @return string
	 */
	public function frontOpenPay($orderId,$txnAmt,$accNo,$customerInfo,$ext = null){
		$params = array(
			'version' => $this->config['version'],
			'signMethod' =>  UnionPay::SIGNMETHOD_RSA,
			'encoding' => 'utf-8',
			'txnType' => '01',
			'txnSubType' => '01',
			'bizType' => UnionPay::BIZTYPE_DIRECT,
			'accessType' => UnionPay::ACCESSTYPE_MERCHANT,
			'channelType' => '07',
			'encryptCertId' => $this->getCertIdCer($this->config['encryptCertPath']),
			'currencyCode' => '156',
		);
		$params['merId' ] =  $this->config['merId'];
		$params['orderId'] =  $orderId;
		$params['txnAmt'] =  $txnAmt;
		$params['txnTime'] = date('YmdHis');
		$params['accNo'] =  $this->encryptData($accNo);
		$params['customerInfo'] =  $this->encryptCustomerInfo($customerInfo);

		$params['certId'] =  $this->getSignCertId();
		$params['accType'] = '01';
		$params['frontUrl'] = $this->config['openReturnUrl'];
		$params['backUrl'] = $this->config['openNotifyUrl'];
		$params['payTimeout'] = '';// date('YmdHis', strtotime('+15 minutes')); //问了银联技术支持，让留空，否则测试时会报错：订单已超时

		$params['signature'] = $this->sign($params);
		$result = $this->createPostForm($params,'开通并支付');
		return $result;
	}

	private function encryptCustomerInfo($customerInfo) {
		if($customerInfo == null || count($customerInfo) == 0 )
			return "";
		$sensitive = ['phoneNo','cvn2','expired'];//'certifTp' certifId ??
		$sensitiveInfo = array();
		foreach ( $customerInfo as $key => $value ) {
			if (in_array($key,$sensitive) ) {
				$sensitiveInfo [$key] = $customerInfo [$key];
				unset ( $customerInfo [$key] );
			}
		}
		if( count ($sensitiveInfo) > 0 ){
			$sensitiveInfoStr = $this->arrayToString( $sensitiveInfo ,true);
			$encryptedInfo = $this->encryptData( $sensitiveInfoStr);
			$customerInfo ['encryptedInfo'] = $encryptedInfo;
		}
		return base64_encode ( "{" . $this->arrayToString( $customerInfo ) . "}" );
	}

}