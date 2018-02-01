<?php
namespace zhangv\unionpay;

/**
 * 银联网关支付
 * @license MIT
 * @author zhangv
 * @ref https://open.unionpay.com/ajweb/product/newProDetail?proId=1
 * */
class UnionPay {
	const SIGNMETHOD_RSA = '01',SIGNMETHOD_SHA256 = '11',SIGNMETHOD_SM3 = '12';
	const CHANNELTYPE_PC = '07', CHANNELTYPE_MOBILE = '08';
	const TXNTYPE_CONSUME = '01',TXNTYPE_PREAUTH = '02',TXNTYPE_PREAUTHFINISH = '03',TXNTYPE_REFUND = '04',
		TXNTYPE_CONSUMEUNDO = '31',TXNTYPE_PREAUTHUNDO = '32',TXNTYPE_PREAUTHFINISHUNDO = '33',
		TXNTYPE_FILEDOWNLOAD = '76', TXNTYPE_UPDATEPUBLICKEY = '95';
	public $frontTransUrl = "https://gateway.95516.com/gateway/api/frontTransReq.do";
	public $backTransUrl = "https://gateway.95516.com/gateway/api/backTransReq.do";
	public $batchTransUrl = "https://gateway.95516.com/gateway/api/batchTrans.do";
	public $singleQueryUrl = "https://gateway.95516.com/gateway/api/queryTrans.do";
	public $fileDownloadUrl = "https://filedownload.95516.com/";
	public $cardTransUrl = "https://gateway.95516.com/gateway/api/cardTransReq.do";
	public $appTransUrl = "https://gateway.95516.com/gateway/api/appTransReq.do";
	
	/** @var array 支付配置 */
	protected $config = [];
	/** @var HttpClient */
	protected $httpClient = null;
	/** @var string 自动提交表单模板 */
	protected $formTemplate = <<<'HTML'
<!DOCTYPE HTML>
<html>
<head>
    <meta charset="utf-8">
    <title>银联支付</title>
</head>
<body>
    <div style="text-align:center">跳转中...</div>
    <form id="pay_form" name="pay_form" action="%s" method="post">
        %s
    </form>
    <script type="text/javascript">
        document.onreadystatechange = function(){
            if(document.readyState == "complete") {
                document.pay_form.submit();
            }
        };
    </script>
</body>
</html>
HTML;

	public function __construct($config){
		$this->config = $config;
		$this->httpClient = new HttpClient(3);
	}

	/**
	 * 支付
	 * https://open.unionpay.com/ajweb/product/newProApiShow?proId=1&apiId=63
	 * @param $orderId
	 * @param $amt
	 * @param string $reqReserved
	 * @param string $reserved
	 * @param array $ext
	 * @return string
	 */
	public function pay($orderId,$amt,$reqReserved = '',$reserved = '',$ext = []){
		$params = [
			'version' => '5.0.0',
			'encoding' => 'UTF-8',
			'certId' => $this->getSignCertId(),
			'signMethod' => UnionPay::SIGNMETHOD_RSA,
			'txnType' => UnionPay::TXNTYPE_CONSUME,
			'txnSubType' => '01',
			'bizType' => '000201',
			'channelType' => '07',
			'frontUrl' => $this->config['returnUrl'],
			'backUrl' => $this->config['notifyUrl'],
			'accessType' => '0', //接入类型
			'merId' => $this->config['merId'],
			'orderId' => $orderId,
			'txnTime' => date('YmdHis'),
			'txnAmt' => $amt ,
			'currencyCode' => '156',
			'defaultPayType' => '0001',	//默认支付方式
			'reserved' => $reserved,
			'reqReserved' => $reqReserved,
		];
		if(is_array($ext)) $params = array_merge($params,$ext);
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
	 * 消费撤销
	 * @ref https://open.unionpay.com/ajweb/product/newProApiShow?proId=1&apiId=64
	 * @param string $orderId
	 * @param string $origQryId
	 * @param string $txnAmt
	 * @param string $reserved
	 * @param string $reqReserved
	 * @param array $ext
	 * @return mixed
	 */
	public function payUndo($orderId,$origQryId,$txnAmt,$reserved = '',$reqReserved = '',$ext = []){
		$params = [
			'version' => '5.1.0',
			'encoding' => 'UTF-8',
			'bizType' => '000000',//？？
			'txnTime' => date('YmdHis'),
			'backUrl' => $this->config['returnUrl'],
			'txnAmt' => $txnAmt,
			'txnType' => UnionPay::TXNTYPE_CONSUMEUNDO,
			'txnSubType' => '00',
			'accessType' => '0',
			'signMethod' => UnionPay::SIGNMETHOD_RSA,
			'channelType' => '07',
			'merId' => $this->config['merId'],
			'orderId' => $orderId,
			'origQryId' => $origQryId,
			'certId' => $this->getSignCertId(),
			'reserved' => $reserved,
			'reqReserved' => $reqReserved,
		];
		if(is_array($ext)) $params = array_merge($params,$ext);
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
	 * @ref https://open.unionpay.com/ajweb/product/newProApiShow?proId=1&apiId=65
	 * @param $orderId
	 * @param $origQryId
	 * @param $refundAmt
	 * @param string $reqReserved
	 * @return mixed
	 */
	public function refund($orderId,$origQryId,$refundAmt,$reqReserved = '',$reserved = '',$ext = []){
		$params = [
			'version' => '5.1.0',
			'encoding' => 'UTF-8',
			'certId' => $this->getSignCertId(),
			'signMethod' => UnionPay::SIGNMETHOD_RSA,
			'txnType' => UnionPay::TXNTYPE_REFUND,
			'txnSubType' => '00',
			'bizType' => '000201',
			'accessType' => '0',
			'channelType' => '07',
			'orderId' => $orderId,
			'merId' => $this->config['merId'],
			'origQryId' => $origQryId,
			'txnTime' => date('YmdHis'),
			'txnAmt' => $refundAmt,
			'backUrl' => $this->config['returnUrl'],
			'reqReserved' => $reqReserved,
			'reserved' => $reserved
		];
		if(is_array($ext)) $params = array_merge($params,$ext);
		$params['signature'] = $this->sign($params);
		$result = $this->post($params,$this->backTransUrl);
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
		if($this->validateSign($notifyData)){
			if($callback && is_callable($callback)){
				$queryId = $notifyData['queryId'];
				$traceNo = $notifyData['traceNo'];
				return call_user_func_array( $callback , [$notifyData] );
			}else{
				print('ok');
			}
		}else{
			throw new \Exception('Invalid paid notify data');
		}
	}

	/**
	 * 后台交易 HttpClient通信
	 * @param array $params
	 * @param string $url
	 * @return mixed
	 */
	protected function post($params, $url) {
		$postbody = $this->getRequestParamString ( $params );
		$headers = array ('Content-type:application/x-www-form-urlencoded;charset=UTF-8') ;
		$opts = array(
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_RETURNTRANSFER => true
		);
		//todo handle the respCode
		return $this->httpClient->post($url,$postbody,$headers,$opts);
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
		$result = $this->post($params,$this->singleQueryUrl);
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
		$result = $this->post($params,$this->fileDownloadUrl);
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
			'frontUrl' =>  $this->config['returnUrl'],
			'backUrl' => $this->config['notifyUrl'],
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
		$result = $this->post($params,$this->backTransUrl);
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
			'backUrl' => $this->config['notifyUrl'],
			'reserved' => $reserved,
			'reqReserved' => $reqReserved,
		);
		$params['signature'] = $this->sign($params);
		$result = $this->post($params,$this->backTransUrl);
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
			'backUrl' => $this->config['notifyUrl'],
			'reserved' => $reserved,
			'reqReserved' => $reqReserved,
		);
		$params['signature'] = $this->sign($params);
		$result = $this->post($params,$this->backTransUrl);
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
			'backUrl' => $this->config['notifyUrl'],
			'reserved' => $reserved,
			'reqReserved' => $reqReserved,
		);
		$params['signature'] = $this->sign($params);
		$result = $this->post($params,$this->backTransUrl);
		return $result;
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
		$result = $this->post($params,$this->backTransUrl);
		return $result;
	}

	/**
	 * 取签名证书ID(SN)
	 * @return string
	 */
	public function getSignCertId(){
		return $this->getCertIdPfx($this->config['signCertPath']);
	}

	/**
	 * 取.pfx格式证书ID(SN)
	 * @return string
	 */
	protected function getCertIdPfx($path){
		$pkcs12certdata = file_get_contents($path);
		openssl_pkcs12_read($pkcs12certdata, $certs, $this->config['signCertPwd']);
		$x509data = $certs['cert'];
		openssl_x509_read($x509data);
		$certdata = openssl_x509_parse($x509data);
		return $certdata['serialNumber'];
	}

	/**
	 * 构建自动提交HTML表单
	 * @return string
	 */
	protected function createPostForm($params){
		$input = '';
		foreach($params as $key => $item) {
			$input .= "\t\t<input type=\"hidden\" name=\"{$key}\" value=\"{$item}\">\n";
		}
		return sprintf($this->formTemplate, $this->frontTransUrl, $input);
	}

	/**
	 * 签名数据
	 * @param $params
	 * @param string $signType
	 * @throws \InvalidArgumentException
	 * @return string
	 */
	protected function sign($params,$signType = UnionPay::SIGNMETHOD_RSA) {
		$signData = $params;
		ksort($signData);
		$signQueryString = $this->arrayToString($signData);
		if($signType == UnionPay::SIGNMETHOD_RSA) {
			if($params['version'] == '5.0.0') {
				//签名之前先用sha1处理
				$datasha1 = sha1($signQueryString);
				$signed = $this->rsaSign($datasha1);
				return $signed;
			}elseif($params['version'] == '5.1.0') {
				//签名之前先用sha1处理
				$datasha1 = sha1($signQueryString);
				$signed = $this->rsaSign($datasha1);
				return $signed;
			}
		} else {
			throw new \InvalidArgumentException('Unsupported Sign Method');
		}
	}

	/**
	 * 数组转换成字符串
	 * @param array $arr
	 * @return string
	 */
	protected function arrayToString($arr){
		$str = '';
		foreach($arr as $key => $value) {
			if(trim($value)=='') continue;
			$str .= $key.'='.$value.'&';
		}
		return substr($str, 0, strlen($str) - 1);
	}

	/**
	 * RSA签名数据，并base64编码
	 * @param string $data 待签名数据
	 * @return mixed
	 */
	protected function rsaSign($data){
		$privatekey = $this->getSignPrivateKey();
		$result = openssl_sign($data, $signature, $privatekey);
		if($result) return base64_encode($signature);
		return false;
	}

	/**
	 * 取签名证书私钥
	 * @return resource
	 */
	protected function getSignPrivateKey(){
		$pkcs12 = file_get_contents($this->config['signCertPath']);
		openssl_pkcs12_read($pkcs12, $certs, $this->config['signCertPwd']);
		return $certs['pkey'];
	}

	/**
	 * 验证签名
	 * @throws \Exception
	 * @return bool
	 */
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
	 * 取验证签名证书
	 * @throws \InvalidArgumentException
	 * @return string
	 */
	protected function getVerifyPublicKey(){
		return file_get_contents($this->config['verifyCertPath']);
	}

	/**
	 * 取.cer格式证书ID(SN)
	 * @return string
	 */
	protected function getCertIdCer($path){
		$x509data = file_get_contents($path);
		openssl_x509_read($x509data);
		$certdata = openssl_x509_parse($x509data);
		return $certdata['serialNumber'];
	}
}