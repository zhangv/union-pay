<?php
namespace zhangv\unionpay;

/**
 * 银联无跳转支付(Token版)
 * @license MIT
 * @author zhangv
 * @ref https://open.unionpay.com/ajweb/product/newProDetail?proId=2&cataId=20
 * */
class UnionPayToken extends UnionPayDirect {

	/**
	 * 申请token
	 * @param $orderId
	 * @param $tokenPayData
	 * @param array $ext
	 * @return mixed
	 */
	public function applyToken($orderId,$txnTime,$tokenPayData,$ext = []){
		$params = array(
			'version' => $this->config['version'],
			'signMethod' =>  UnionPay::SIGNMETHOD_RSA,
			'encoding' => 'UTF-8',
			'txnType' => '79',
			'txnSubType' => '05',
			'bizType' => UnionPay::BIZTYPE_TOKEN,
			'accessType' => UnionPay::ACCESSTYPE_MERCHANT,
			'channelType' => '07',
			'encryptCertId' => $this->getCertIdCer($this->config['encryptCertPath']),
		);
		$params['merId' ] =  $this->config['merId'];
		$params['orderId'] =  $orderId;
		$params['txnTime'] = $txnTime;
		$params['txnTime'] = $txnTime;
		$params['tokenPayData'] = $tokenPayData;
		$params = array_merge($params,$ext);
		$params['signature'] = $this->sign($params);
		$result = $this->post($params,$this->backTransUrl);
		return $result;
	}

	/**
	 * 后台开通（需要用申请的商户号，并授权后方可测试）
	 * @param $orderId
	 * @param $accNo
	 * @param $customerInfo
	 * @param array $ext
	 * @return mixed
	 */
	public function backOpen($orderId,$accNo,$customerInfo,$ext = []){
		$ext['bizType'] = UnionPay::BIZTYPE_TOKEN;
		return parent::backOpen($orderId,$accNo,$customerInfo,$ext);
	}

	/**
	 * 前台开通
	 * @param $orderId
	 * @param $accNo
	 * @param $customerInfo
	 * @param array $ext
	 * @return string
	 */
	public function frontOpen($orderId,$accNo,$customerInfo,$ext = []){
		$ext['bizType'] = UnionPay::BIZTYPE_TOKEN;
		return parent::frontOpen($orderId,$accNo,$customerInfo,$ext);
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
	public function queryOpen($orderId,$accNo){
		$ext['bizType'] = UnionPay::BIZTYPE_TOKEN;
		return parent::queryOpen($orderId,$accNo);
	}

	/**
	 * 删除token
	 * @param string $orderId
	 * @param string $txnTime
	 * @param string $token
	 * @return array
	 */
	public function deleteToken($orderId,$txnTime,$token){
		$params = array(
			'version' => $this->config['version'],
			'signMethod' =>  UnionPay::SIGNMETHOD_RSA,
			'encoding' => 'UTF-8',
			'txnType' => '74',
			'txnSubType' => '01',
			'bizType' => UnionPay::BIZTYPE_TOKEN,
			'accessType' => UnionPay::ACCESSTYPE_MERCHANT,
			'channelType' => '07',
			'encryptCertId' => $this->getCertIdCer($this->config['encryptCertPath']),
		);
		$params['merId' ] =  $this->config['merId'];
		$params['orderId'] =  $orderId;
		$params['txnTime'] = $txnTime;
		$params['tokenPayData'] = "{trId=62000000001&token=$token}";
		$params['signature'] = $this->sign($params);
		$result = $this->post($params,$this->backTransUrl);
		return $result;
	}

	/**
	 * 更新token
	 * @param string $orderId
	 * @param string $txnTime
	 * @param string $token
	 * @return array
	 */
	public function updateToken($orderId,$txnTime,$token,$customerInfo){
		$params = array(
			'version' => $this->config['version'],
			'signMethod' =>  UnionPay::SIGNMETHOD_RSA,
			'encoding' => 'UTF-8',
			'txnType' => '79',
			'txnSubType' => '03',
			'bizType' => UnionPay::BIZTYPE_TOKEN,
			'accessType' => UnionPay::ACCESSTYPE_MERCHANT,
			'channelType' => '07',
			'encryptCertId' => $this->getCertIdCer($this->config['encryptCertPath']),
		);
		$params['merId' ] =  $this->config['merId'];
		$params['orderId'] =  $orderId;
		$params['txnTime'] = $txnTime;
		$params['customerInfo'] =  $this->encryptCustomerInfo($customerInfo);
		$params['tokenPayData'] = "{trId=62000000001&token=$token&tokenType=01}";

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
		$params['signature'] = $this->sign($params);
		$result = $this->post($params,$this->backTransUrl);
		return $result;
	}


	/**
	 * 支付
	 * @param $orderId
	 * @param $txnAmt
	 * @param array $ext
	 * @return array
	 */
	public function pay($orderId,$txnAmt,$ext = []){
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
		$params = array_merge($ext,$params);
		$params['signature'] = $this->sign($params);
		$result = $this->post($params,$this->backTransUrl);
		return $result;
	}

	/**
	 * 分期付款
	 * @param $orderId
	 * @param $txnAmt
	 * @param array $ext
	 * @return array
	 */
	public function payByInstallment($orderId,$txnAmt,$ext = []){
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
		$params = array_merge($ext,$params);
		$params['signature'] = $this->sign($params);
		$result = $this->post($params,$this->backTransUrl);
		return $result;
	}

	/**
	 * 前台开通并支付
	 * @param $orderId
	 * @param $accNo
	 * @param $customerInfo
	 * @param array $ext
	 * @return string
	 */
	public function frontOpenPay($orderId,$txnAmt,$accNo,$customerInfo,$ext = []){
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

	/**
	 * 支付撤销
	 * @param $orderId
	 * @param $origQryId
	 * @param $txnAmt
	 * @param array $ext
	 * @return array
	 */
	public function payUndo($orderId,$origQryId,$txnAmt,$ext = []){
		$params = array(
			'version' => $this->config['version'],
			'signMethod' =>  UnionPay::SIGNMETHOD_RSA,
			'encoding' => 'utf-8',
			'txnType' => '31',
			'txnSubType' => '00',
			'bizType' => UnionPay::BIZTYPE_DIRECT,
			'accessType' => UnionPay::ACCESSTYPE_MERCHANT,
			'channelType' => '07',
			'backUrl' => $this->config['notifyUrl']
		);
		$params['merId' ] =  $this->config['merId'];
		$params['orderId'] =  $orderId;
		$params['txnTime'] = date('YmdHis');
		$params['txnAmt'] = $txnAmt;
		$params['origQryId'] = $origQryId;
		$params['certId'] =  $this->getSignCertId();
		$params = array_merge($ext,$params);
		$params['signature'] = $this->sign($params);
		$result = $this->post($params,$this->backTransUrl);
		return $result;
	}

	/**
	 * 退款
	 * @param $orderId
	 * @param $origQryId
	 * @param $txnAmt
	 * @param array $ext
	 * @return array
	 */
	public function refund($orderId,$origQryId,$txnAmt,$ext = []){
		$params = array(
			'version' => $this->config['version'],
			'signMethod' =>  UnionPay::SIGNMETHOD_RSA,
			'encoding' => 'utf-8',
			'txnType' => '04',
			'txnSubType' => '00',
			'bizType' => UnionPay::BIZTYPE_DIRECT,
			'accessType' => UnionPay::ACCESSTYPE_MERCHANT,
			'channelType' => '07',
			'backUrl' => $this->config['notifyUrl']
		);
		$params['merId' ] =  $this->config['merId'];
		$params['orderId'] =  $orderId;
		$params['txnTime'] = date('YmdHis');
		$params['txnAmt'] = $txnAmt;
		$params['origQryId'] = $origQryId;
		$params['certId'] =  $this->getSignCertId();
		$params = array_merge($ext,$params);
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
		return parent::updatePublicKey($orderId,$ext);
	}

	/**
	 * 交易状态查询
	 * @ref https://open.unionpay.com/ajweb/product/newProApiShow?proId=1&apiId=66
	 * @param $orderId
	 * @param array $ext
	 * @return mixed
	 */
	public function query($orderId,$ext = []){
		return parent::query($orderId,$ext);
	}

	/**
	 * 文件传输
	 * @ref https://open.unionpay.com/ajweb/product/newProApiShow?proId=1&apiId=72
	 * @param string $settleDate MMDD
	 * @param string $fileType
	 * @return mixed
	 */
	public function fileDownload($settleDate,$fileType = '00'){
		parent::fileDownload($settleDate,$fileType);
	}

}