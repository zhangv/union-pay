<?php
namespace zhangv\unionpay\service;
use zhangv\unionpay\UnionPay;
/**
 * 无跳转支付(Token版)
 * @license MIT
 * @author zhangv
 * @ref https://open.unionpay.com/ajweb/product/newProApiList?proId=2
 * */
class DirectToken extends Direct {

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
			'txnType' => UnionPay::TXNTYPE_APPLYTOKEN,
			'txnSubType' => '05',
			'bizType' => UnionPay::BIZTYPE_TOKEN,
			'accessType' => UnionPay::ACCESSTYPE_MERCHANT,
			'channelType' => '07',
			'encryptCertId' => $this->getCertIdCer($this->config['encryptCertPath']),
		);
		$params['certId'] =  $this->getSignCertId();
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

	/**
	 * 开通通知处理
	 * @param $notifyData
	 * @param callable $callback
	 * @return mixed
	 * @throws \Exception
	 */
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
	 * @param array $ext
	 * @return array
	 */
	public function queryOpen($orderId,$accNo,$ext = []){
		$ext['bizType'] = UnionPay::BIZTYPE_TOKEN;
		return parent::queryOpen($orderId,$accNo,$ext);
	}

	/**
	 * 删除token
	 * @param string $orderId
	 * @param string $txnTime
	 * @param string $tokenPayData
	 * @return array
	 */
	public function deleteToken($orderId,$txnTime,$tokenPayData){
		$params = array(
			'version' => $this->config['version'],
			'signMethod' =>  UnionPay::SIGNMETHOD_RSA,
			'encoding' => 'UTF-8',
			'txnType' => UnionPay::TXNTYPE_DELETETOKEN,
			'txnSubType' => '01',
			'bizType' => UnionPay::BIZTYPE_TOKEN,
			'accessType' => UnionPay::ACCESSTYPE_MERCHANT,
			'channelType' => '07',
			'encryptCertId' => $this->getCertIdCer($this->config['encryptCertPath']),
		);
		$params['certId'] =  $this->getSignCertId();
		$params['merId' ] =  $this->config['merId'];
		$params['orderId'] =  $orderId;
		$params['txnTime'] = $txnTime;
		$params['tokenPayData'] = $tokenPayData;
		$params['signature'] = $this->sign($params);
		$result = $this->post($params,$this->backTransUrl);
		return $result;
	}

	/**
	 * 更新token
	 * @param string $orderId
	 * @param string $txnTime
	 * @param array $customerInfo
	 * @param string $tokenPayData
	 * @return array
	 */
	public function updateToken($orderId,$txnTime,$customerInfo,$tokenPayData){
		$params = array(
			'version' => $this->config['version'],
			'signMethod' =>  UnionPay::SIGNMETHOD_RSA,
			'encoding' => 'UTF-8',
			'txnType' => UnionPay::TXNTYPE_APPLYTOKEN,
			'txnSubType' => '03',
			'bizType' => UnionPay::BIZTYPE_TOKEN,
			'accessType' => UnionPay::ACCESSTYPE_MERCHANT,
			'channelType' => '07',
			'encryptCertId' => $this->getCertIdCer($this->config['encryptCertPath']),
		);
		$params['certId'] =  $this->getSignCertId();
		$params['merId' ] =  $this->config['merId'];
		$params['orderId'] =  $orderId;
		$params['txnTime'] = $txnTime;
		$params['customerInfo'] =  $this->encryptCustomerInfo($customerInfo);
		$params['tokenPayData'] = $tokenPayData;

		$params['signature'] = $this->sign($params);
		$result = $this->post($params,$this->backTransUrl);
		return $result;
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
	public function sms($orderId,$accNo,$customerInfo,$smsType = Direct::SMSTYPE_OPEN,$ext = []):array{
		$ext['bizType'] = UnionPay::BIZTYPE_TOKEN;
		return parent::sms($orderId,$accNo,$customerInfo,$smsType,$ext);
	}


	/**
	 * 使用Token支付
	 * @param $orderId
	 * @param $txnAmt
	 * @param array $ext
	 * @return array
	 */
	public function payByToken($orderId,$txnAmt,$txnTime,$tokenPayData,$ext = []){
		$params = array(
			'version' => $this->config['version'],
			'signMethod' =>  UnionPay::SIGNMETHOD_RSA,
			'encoding' => 'utf-8',
			'txnType' => UnionPay::TXNTYPE_CONSUME,
			'txnSubType' => '01', //01 - 自助消费  03 - 分期付款
			'bizType' => UnionPay::BIZTYPE_TOKEN,
			'accessType' => UnionPay::ACCESSTYPE_MERCHANT,
			'channelType' => '07',
			'currencyCode' => '156',          //交易币种，境内商户勿改
			'encryptCertId' => $this->getCertIdCer($this->config['encryptCertPath']),
			'backUrl' => $this->config['notifyUrl']
		);
		$params['merId' ] =  $this->config['merId'];
		$params['orderId'] =  $orderId;
		$params['txnTime'] = $txnTime;
		$params['txnAmt'] = $txnAmt;
		$params['tokenPayData'] = $tokenPayData;
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
			'txnType' => UnionPay::TXNTYPE_CONSUME,
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
		return parent::payUndo($orderId,$origQryId,$txnAmt,$ext);
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
		return parent::refund($orderId,$origQryId,$txnAmt,$ext);
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
		parent::fileDownload($settleDate,$fileType);
	}

}