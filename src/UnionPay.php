<?php
namespace zhangv\unionpay;

/**
 * 银联网关支付
 * @license MIT
 * @author zhangv
 * @ref https://open.unionpay.com/ajweb/product/newProDetail?proId=1
 * */
class UnionPay {
	const MODE_TEST = 'test',MODE_PROD = 'prod';
	const SIGNMETHOD_RSA = '01',SIGNMETHOD_SHA256 = '11',SIGNMETHOD_SM3 = '12';
	const CHANNELTYPE_PC = '07', CHANNELTYPE_MOBILE = '08';
	const TXNTYPE_CONSUME = '01',TXNTYPE_PREAUTH = '02',TXNTYPE_PREAUTHFINISH = '03',TXNTYPE_REFUND = '04',
		TXNTYPE_CONSUMEUNDO = '31',TXNTYPE_PREAUTHUNDO = '32',TXNTYPE_PREAUTHFINISHUNDO = '33',
		TXNTYPE_FILEDOWNLOAD = '76', TXNTYPE_UPDATEPUBLICKEY = '95';
	const RESPCODE_SUCCESS = '00';
	public $frontTransUrl = "https://gateway.95516.com/gateway/api/frontTransReq.do";
	public $backTransUrl = "https://gateway.95516.com/gateway/api/backTransReq.do";
	public $batchTransUrl = "https://gateway.95516.com/gateway/api/batchTrans.do";
	public $singleQueryUrl = "https://gateway.95516.com/gateway/api/queryTrans.do";
	public $fileDownloadUrl = "https://filedownload.95516.com/";
	public $cardTransUrl = "https://gateway.95516.com/gateway/api/cardTransReq.do";
	public $appTransUrl = "https://gateway.95516.com/gateway/api/appTransReq.do";

	public $response;
	public $responseArray;
	public $respCode,$respMsg;
	public static $verifyCerts510 = [];
	public static $verifyPublicKeys = [];
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
    <div style="text-align:center">%s跳转中...</div>
    <form id="pay_form" name="pay_form" action="%s" method="post">
        %s
        <button>提交</button>
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

	public function __construct($config,$mode = UnionPay::MODE_PROD){
		$this->config = $config;
		$this->httpClient = new HttpClient(3);
		if($mode == UnionPay::MODE_TEST){
			$this->frontTransUrl = 'https://gateway.test.95516.com/gateway/api/frontTransReq.do';
			$this->backTransUrl = 'https://gateway.test.95516.com/gateway/api/backTransReq.do';
			$this->singleQueryUrl = 'https://gateway.test.95516.com/gateway/api/queryTrans.do';
			$this->fileDownloadUrl = 'https://filedownload.test.95516.com/';
		}
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
			'version' => $this->config['version'],
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
	 * @param bool $validateResp
	 * @return mixed
	 * @throws \Exception
	 */
	protected function post($params, $url, $validateResp = true) {
		$postbody = $this->getRequestParamString ( $params );
		$headers = array ('Content-type:application/x-www-form-urlencoded;charset=UTF-8') ;
		$opts = array(
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSLVERSION => 1
		);
		$this->response = $this->httpClient->post($url,$postbody,$headers,$opts);
		$this->responseArray = $this->convertQueryStringToArray($this->response);
//		var_dump($this->responseArray);
		if($validateResp == true && !$this->validateSign($this->responseArray)){
			throw new \Exception("Signature verification failed");
		}
		$this->respCode = $this->responseArray['respCode'];
		$this->respMsg = $this->responseArray['respMsg'];
		if($this->respCode == UnionPay::RESPCODE_SUCCESS){
			return $this->responseArray;
		}else{
			throw new \Exception($this->respMsg);
		}

	}

	private function convertQueryStringToArray($query){
		$r = explode('&',$query);
		$rr = [];
		foreach($r as $v){
			$tmp = explode('=',$v);
			$rr[$tmp[0]] = $tmp[1];
		}
		return $rr;
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
			if(trim($value)=='') continue;
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
			'version' => '5.0.0', //only 5.0.0
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
		$params['signature'] = $this->sign($params);
		$result = $this->post($params,$this->singleQueryUrl,false);
		return $result;
	}

	/**
	 * 文件传输
	 * @ref https://open.unionpay.com/ajweb/product/newProApiShow?proId=1&apiId=72
	 * @param string $settleDate MMDD
	 * @param string $fileType
	 * @return mixed
	 */
	public function fileDownload($settleDate,$fileType = '00'){
		$params = array(
			'version' => '5.0.0', //only 5.0.0
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
		$result = $this->post($params,$this->fileDownloadUrl,false);
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
			'version' => $this->config['version'],
			'encoding' => 'UTF-8',
			'certId' => $this->getSignCertId (),
			'txnType' => UnionPay::TXNTYPE_PREAUTH,
			'txnSubType' => '01',
			'bizType' => '000201',
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
			'reqReserved' => $reqReserved,
		);
		$params['signature'] = $this->sign($params);
		$result = $this->createPostForm($params,'预授权');
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
	 * @param $params
	 * @param $title
	 * @return string
	 */
	protected function createPostForm($params,$title = '支付'){
		$input = '';
		foreach($params as $key => $item) {
			if(trim($item)=='') continue;
			$input .= "\t\t<input type=\"hidden\" name=\"{$key}\" value=\"{$item}\">\n";
		}
		return sprintf($this->formTemplate, $title,$this->frontTransUrl, $input);
	}

	/**
	 * 签名数据
	 * @param $params
	 * @param string $signMethod
	 * @throws \Exception
	 * @return string
	 */
	protected function sign($params,$signMethod = UnionPay::SIGNMETHOD_RSA) {
		$signData = $params;
		ksort($signData);
		$signQueryString = $this->arrayToString($signData);
		if($signMethod == UnionPay::SIGNMETHOD_RSA) {
			if($params['version'] == '5.0.0'){
				$datasha1 = sha1($signQueryString);
				$signed = $this->rsaSign($datasha1);
				return $signed;
			}elseif($params['version'] == '5.1.0'){
				$sha256 = hash( 'sha256',$signQueryString);
				$privateKey = $this->getSignPrivateKey();
				$result = openssl_sign ( $sha256, $signature, $privateKey, 'sha256');
				if ($result) {
					$signature_base64 = base64_encode ( $signature );
					return $signature_base64;
				} else {
					throw new \Exception("Error while signing");
				}
			}else throw new \Exception("Unsuported version - {$params['version']}");
		} else {
			throw new \Exception("Unsupported Sign Method - {$signMethod}");
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
	 * 签名证书私钥
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
		if($params['signMethod'] == UnionPay::SIGNMETHOD_RSA){
			$signature = base64_decode($params['signature']);
			$verifyArr = $params;
			unset($verifyArr['signature']);
			ksort($verifyArr);
			$verifyStr = $this->arrayToString($verifyArr);

			if($params['version'] == '5.0.0'){ //测试环境公钥证书不正确
				$certId = $params['certId'];
				$publicKey = $this->getVerifyPublicKey($certId);
				$verifySha1 = sha1($verifyStr,FALSE);
				$result = openssl_verify($verifySha1, $signature, $publicKey,OPENSSL_ALGO_SHA1);
				if($result === -1) {
					throw new \Exception('Verify Error:'.openssl_error_string());
				}
				return $result;
			}elseif($params['version'] == '5.1.0'){
				$signPubKeyCert = $params['signPubKeyCert'];
				$cert = $this->verifyAndGetVerifyCert($signPubKeyCert);
				if($cert == null){
					return false;
				}else{
					$verifySha256 = hash('sha256', $verifyStr);
//					$result = openssl_verify ( $verifySha256, $signature,$cert, "sha256" );
					$result = openssl_verify ( $verifySha256, $signature,$cert, OPENSSL_ALGO_SHA256 );
					if($result === -1) {
						throw new \Exception('Verify Error:'.openssl_error_string());
					}
					return $result;
				}
			}else throw new \Exception("Unsupported version {$params['version']}");
		}else{
			return $this->validateBySecureKey($params,$this->config['secureKey']);
		}
	}

	public function verifyAndGetVerifyCert($certBase64String){
		if (array_key_exists($certBase64String, UnionPay::$verifyCerts510)){
			return UnionPay::$verifyCerts510[$certBase64String];
		}

		if(trim($this->config['verifyRootCertPath']) == '' || trim($this->config['verifyMiddleCertPath']) == ''){
			throw new \Exception("Root certificate and middle certificate should be configured");
		}
		openssl_x509_read($certBase64String);
		$certInfo = openssl_x509_parse($certBase64String);

		$cn = $this->getIdentitiesFromCertficate($certInfo);
		if($this->config['ifValidateCNName'] === true){
			if("中国银联股份有限公司" != $cn){
				return null;
			}
		}elseif("中国银联股份有限公司" != $cn && "00040000:SIGN" != $cn){
			return null;
		}

		$from = date_create ( '@' . $certInfo ['validFrom_time_t'] );
		$to = date_create ( '@' . $certInfo ['validTo_time_t'] );
		$now = date_create ( date ( 'Ymd' ) );
		$interval1 = $from->diff ( $now );
		$interval2 = $now->diff ( $to );
		if ($interval1->invert || $interval2->invert) {
			throw new \Exception("signPubKeyCert has expired");
		}
		$result = openssl_x509_checkpurpose($certBase64String, X509_PURPOSE_ANY,
			array(
				$this->config['verifyRootCertPath'],
				$this->config['verifyMiddleCertPath']
			)
		);
		if($result === FALSE){
			return null;
		} else if($result === TRUE){
			UnionPay::$verifyCerts510[$certBase64String] = $certBase64String;
			return UnionPay::$verifyCerts510[$certBase64String];
		} else {
			throw new \Exception("validate signPubKeyCert by rootCert failed with error");
		}
	}

	protected function getIdentitiesFromCertficate($certInfo){
		$cn = $certInfo['subject'];
		$cn = $cn['CN'];
		$company = explode('@',$cn);
		if(count($company) < 3) {
			return null;
		}
		return $company[2];
	}

	/**
	 * 获取验证公钥
	 * @param string $certId
	 * @throws \Exception
	 * @return string
	 */
	protected function getVerifyPublicKey($certId){
		if(isset(self::$verifyPublicKeys[$certId])){
			return self::$verifyPublicKeys[$certId];
		}
		$pubkeys = $this->getVerifyPublicKeyByCerts([
			$this->config['verifyCertPath'],
			$this->config['verifyRootCertPath'],
			$this->config['verifyMiddleCertPath'],
			$this->config['encryptCertPath'],
		]);
		if(!isset($pubkeys[$certId])) throw new \Exception("Public key not found with certificate id ($certId), existing ones " . implode(',',array_keys($pubkeys)));
		return $pubkeys[$certId];
	}

	protected function getVerifyPublicKeyByCerts(array $paths){
		foreach($paths as $path){
			$x509data = file_get_contents($path);
			openssl_x509_read($x509data);
			$certdata = openssl_x509_parse($x509data);
			$sn = $certdata['serialNumber'];
			if(empty(self::$verifyPublicKeys[$sn])){
				self::$verifyPublicKeys[$sn] = $x509data;
			}
		}
		return self::$verifyPublicKeys;
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

	protected function validateBySecureKey($params, $secureKey) {
		$signature = $params['signature'];
		$verifyArr = $params;
		unset($verifyArr['signature']);
		ksort($verifyArr);
		$verifyStr = $this->arrayToString($verifyArr);
		if($params['signMethod']== UnionPay::SIGNMETHOD_SHA256) {
			$sha256secureKey = hash('sha256', $secureKey);
			$params_before_sha256 = $verifyStr.'&'.$sha256secureKey;
			$params_after_sha256 = hash('sha256',$params_before_sha256);
			return $params_after_sha256 == $signature;
		} else if($params['signMethod']== UnionPay::SIGNMETHOD_SM3) {
			throw new \Exception("Unsupported signmethod - {$params['signMethod']}");
		} else {
			return false;
		}
	}

}