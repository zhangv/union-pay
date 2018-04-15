<?php
namespace zhangv\unionpay;

use \Exception;
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
	const BIZTYPE_GATEWAY = '000201', //网关
		BIZTYPE_DIRECT = '000301', //认证支付（无跳转标准版）
		BIZTYPE_TOKEN = '000902'; //Token支付（无跳转token版）
	const ACCESSTYPE_MERCHANT = '0',//商户直连接入
		ACCESSTYPE_ACQUIRER = '1',//收单机构接入
		ACCESSTYPE_PLATFORM = '2';//平台商户接入
	const RESPCODE_SUCCESS = '00',RESPCODE_SIGNATURE_VERIFICATION_FAIL = '11';
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
//                document.pay_form.submit();
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
			'encoding' => $this->config['encoding'],
			'certId' => $this->getSignCertId(),
			'signMethod' => UnionPay::SIGNMETHOD_RSA,
			'txnType' => UnionPay::TXNTYPE_CONSUME,
			'txnSubType' => '01',
			'bizType' => UnionPay::BIZTYPE_GATEWAY,
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
			'version' => $this->config['version'],
			'encoding' => $this->config['encoding'],
			'bizType' => '000000',
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
	 * @param string $reserved
	 * @param array $ext
	 * @return mixed
	 */
	public function refund($orderId,$origQryId,$refundAmt,$reqReserved = '',$reserved = '',$ext = []){
		$params = [
			'version' => $this->config['version'],
			'encoding' => $this->config['encoding'],
			'certId' => $this->getSignCertId(),
			'signMethod' => UnionPay::SIGNMETHOD_RSA,
			'txnType' => UnionPay::TXNTYPE_REFUND,
			'txnSubType' => '00',
			'bizType' => UnionPay::BIZTYPE_GATEWAY,
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
//				$queryId = $notifyData['queryId'];
//				$traceNo = $notifyData['traceNo'];
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
//		var_dump($this->response);
		$this->responseArray = $this->convertQueryStringToArray($this->response);
//		var_dump($this->responseArray);
		if(count($this->responseArray) ===0 || array_keys($this->responseArray) === range(0,count($this->responseArray)-1)){//not associated array
			if(count($this->responseArray) === 0){
				throw new Exception("No response from remote host");
			}else{
				throw new Exception("Response error - {$this->responseArray[0]}");
			}
		}else{
			$this->respCode = $this->responseArray['respCode'];
			$this->respMsg = $this->responseArray['respMsg'];
			if($this->respCode == UnionPay::RESPCODE_SUCCESS){
				if($validateResp == true && !$this->validateSign($this->responseArray)){
					$a = $this->decryptData($this->responseArray['accNo']);
					throw new \Exception("Signature verification failed");
				}else {
					return $this->responseArray;
				}
			}else{
				throw new \Exception($this->respMsg);
			}
		}
	}

	private function convertQueryStringToArray($query){
		if(!$query || trim($query)==='') {
			return [];
		}
		$r = explode('&',$query);
		$rr = [];
		foreach($r as $v){
			$tmp = explode('=',$v,2); //NOTE: the signature contains '==', so only the first '=' should be taken
			if(count($tmp)>1){
				$rr[$tmp[0]] = $tmp[1];
			}else{
				$rr[] = $tmp[0];
			}
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
			'encoding' => $this->config['encoding'],
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
			'version' => $this->config['version'],
			'encoding' => $this->config['encoding'],
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
			'encoding' => $this->config['encoding'],
			'certId' => $this->getSignCertId (),
			'txnType' => UnionPay::TXNTYPE_PREAUTH,
			'txnSubType' => '01',
			'bizType' => UnionPay::BIZTYPE_GATEWAY,
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
			'version' => $this->config['version'],
			'encoding' => $this->config['encoding'],
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
			'version' => $this->config['version'],
			'encoding' => $this->config['encoding'],
			'certId' => $this->getSignCertId (),
			'signMethod' => UnionPay::SIGNMETHOD_RSA,
			'txnType' => UnionPay::TXNTYPE_PREAUTHFINISH,
			'txnSubType' => '00',
			'bizType' => UnionPay::BIZTYPE_GATEWAY,
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
			'version' => $this->config['version'],
			'encoding' => $this->config['encoding'],
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
			'version' => $this->config['version'],
			'encoding' => $this->config['encoding'],
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
	 * 取签名证书ID(SN)
	 * @return string
	 */
	public function getEncryptCertId(){
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
	 * 取.cer格式证书ID(SN)
	 * @return string
	 */
	protected function getCertIdCer($path){
		$x509data = file_get_contents($path);
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
		$signQueryString = $this->arrayToString($signData,true);
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
			}else throw new \Exception("Unsupported version - {$params['version']}");
		} else {
			throw new \Exception("Unsupported Sign Method - {$signMethod}");
		}
	}

	/**
	 * 数组转换成字符串
	 * @param array $arr
	 * @param boolean $sort
	 * @return string
	 */
	protected function arrayToString($arr,$sort = false){
		$str = '';
		$para = $arr;
		if($sort){
			ksort ( $para );
			reset ( $para );
		}
		foreach($para as $key => $value) {
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
			$signaturebase64 = $params['signature'];
			$verifyArr = $params;
			unset($verifyArr['signature']);
			ksort($verifyArr);
			$verifyStr = $this->arrayToString($verifyArr);

			if($params['version'] == '5.0.0'){ //测试环境公钥证书不正确
				$certId = $params['certId'];
				$publicKey = $this->getVerifyPublicKey($certId);
				$verifySha1 = sha1($verifyStr,FALSE);
				$signature = base64_decode($signaturebase64);
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

//					$verifyStr = preg_replace("/\r\n|\r|\n/", "\r\n", $verifyStr);
//					$verifyStr = "accNo=cjsqgmTALZk1Rcb/l0GL+WKoExXkdZPv+kEezrB0+qpw0qQNNXjCDnV65peho8RyqPchKR3uX22Ov9A5mkUsoUtQD8Z9p1dBxv/s0C+fZOLHJz3LkJJL8xDgfAS7OGghS7gKRJt05S5WDnC5SBoIvb5+PFCB9gjOEJrOBYE3YgwBqQ/UQbPpVsk5FnOKlYQyHC5Z/BBz5YhUbarjAKwBN8aY3aLpD+PN0ii535XuMV2ZTnnkKvVtiWNHHZf5HOD5qgUOR83QSAQSEw6/5inRqI6miWCbAVeidk0JbOIqbElXUeiPDwFvGx6DmBWsydqKI4iQsfYBIrdScevzZnGvHg==&accessType=0&bizType=000301&currencyCode=156&encoding=utf-8&merId=777290058158470&orderId=20180414025538&queryId=121804140255385818028&respCode=00&respMsg=成功[0000000]&signMethod=01&signPubKeyCert=-----BEGIN CERTIFICATE-----
//MIIEQzCCAyugAwIBAgIFEBJJZVgwDQYJKoZIhvcNAQEFBQAwWDELMAkGA1UEBhMC
//Q04xMDAuBgNVBAoTJ0NoaW5hIEZpbmFuY2lhbCBDZXJ0aWZpY2F0aW9uIEF1dGhv
//cml0eTEXMBUGA1UEAxMOQ0ZDQSBURVNUIE9DQTEwHhcNMTcxMTAxMDcyNDA4WhcN
//MjAxMTAxMDcyNDA4WjB3MQswCQYDVQQGEwJjbjESMBAGA1UEChMJQ0ZDQSBPQ0Ex
//MQ4wDAYDVQQLEwVDVVBSQTEUMBIGA1UECxMLRW50ZXJwcmlzZXMxLjAsBgNVBAMU
//JTA0MUBaMjAxNy0xMS0xQDAwMDQwMDAwOlNJR05AMDAwMDAwMDEwggEiMA0GCSqG
//SIb3DQEBAQUAA4IBDwAwggEKAoIBAQDDIWO6AESrg+34HgbU9mSpgef0sl6avr1d
//bD/IjjZYM63SoQi3CZHZUyoyzBKodRzowJrwXmd+hCmdcIfavdvfwi6x+ptJNp9d
//EtpfEAnJk+4quriQFj1dNiv6uP8ARgn07UMhgdYB7D8aA1j77Yk1ROx7+LFeo7rZ
//Ddde2U1opPxjIqOPqiPno78JMXpFn7LiGPXu75bwY2rYIGEEImnypgiYuW1vo9UO
//G47NMWTnsIdy68FquPSw5FKp5foL825GNX3oJSZui8d2UDkMLBasf06Jz0JKz5AV
//blaI+s24/iCfo8r+6WaCs8e6BDkaijJkR/bvRCQeQpbX3V8WoTLVAgMBAAGjgfQw
//gfEwHwYDVR0jBBgwFoAUz3CdYeudfC6498sCQPcJnf4zdIAwSAYDVR0gBEEwPzA9
//BghggRyG7yoBATAxMC8GCCsGAQUFBwIBFiNodHRwOi8vd3d3LmNmY2EuY29tLmNu
//L3VzL3VzLTE0Lmh0bTA5BgNVHR8EMjAwMC6gLKAqhihodHRwOi8vdWNybC5jZmNh
//LmNvbS5jbi9SU0EvY3JsMjQ4NzIuY3JsMAsGA1UdDwQEAwID6DAdBgNVHQ4EFgQU
//mQQLyuqYjES7qKO+zOkzEbvdFwgwHQYDVR0lBBYwFAYIKwYBBQUHAwIGCCsGAQUF
//BwMEMA0GCSqGSIb3DQEBBQUAA4IBAQAujhBuOcuxA+VzoUH84uoFt5aaBM3vGlpW
//KVMz6BUsLbIpp1ho5h+LaMnxMs6jdXXDh/du8X5SKMaIddiLw7ujZy1LibKy2jYi
//YYfs3tbZ0ffCKQtv78vCgC+IxUUurALY4w58fRLLdu8u8p9jyRFHsQEwSq+W5+bP
//MTh2w7cDd9h+6KoCN6AMI1Ly7MxRIhCbNBL9bzaxF9B5GK86ARY7ixkuDCEl4XCF
//JGxeoye9R46NqZ6AA/k97mJun//gmUjStmb9PUXA59fR5suAB5o/5lBySZ8UXkrI
//pp/iLT8vIl1hNgLh0Ghs7DBSx99I+S3VuUzjHNxL6fGRhlix7Rb8
//-----END CERTIFICATE-----&txnAmt=1000&txnSubType=01&txnTime=20180414025538&txnType=01&version=5.1.0";
//					$signaturebase64 = "f5Gz5srn7RvdF2qtAHcakoiwVbSO8cOf9CVX9AJ3oCyjxsdTTXQmx+JQZ8Aw1y2ON+dvFxWC5Z4X/lOmQRSXs3fUZWaErWkgTqBO9Wrl5x3f6FgnB3sGuCXSPs/fm/mXhzv3LVrsmx2EmAxgsuDc7U+eRej/kfwSqI3E2wgHdteQW9jVhG8hxllO7yu9OTfcoPlo87quisMtggeXrfprpuBWKRTPRqsWUypP3+cskVZmc65XL7AGsz74HhS5kwZ9Sc2LejrQKC73Q4wzREdwKUwiPAnoL96ryDqca5+RT1WYq9u3YtxjQUzFTTXypMtZlH92P++MK+rppE9ck5rpyg==";
//					var_dump($verifyStr);
					$verifySha256 = hash('sha256', $verifyStr);
					$signature = base64_decode($signaturebase64);
//					var_dump($verifySha256);
//					var_dump($signaturebase64);
//					var_dump($cert);
					$result = openssl_verify ( $verifySha256, $signature,$cert, "sha256" );
//					var_dump($result);
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

	/**
	 * 检查返回结果中的公钥证书是否有效
	 * @param $certBase64String
	 * @return mixed|null
	 * @throws Exception
	 */
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

	/**
	 * 加密数据
	 * @param string $data
	 * @return string
	 * @throws Exception
	 */
	public function encryptData($data) {
		$cert_path = $this->config['encryptCertPath'];
		$public_key = file_get_contents ( $cert_path );
		if($public_key === false ){
			throw new Exception('Fail reading encrypt certificate');
		}
		if(!openssl_x509_read ( $public_key )){
			throw new Exception( " openssl_x509_read fail。");
		}
		openssl_public_encrypt ( $data, $crypted, $public_key );
		return base64_encode ( $crypted );
	}

	/**
	 * 解密数据
	 * @param string $data
	 * @return string
	 * @throws Exception
	 */
	protected function decryptData($data) {
		$cert_path = $this->config['signCertPath'];
		$cert_pwd = $this->config['signCertPwd'];

		$data = base64_decode ( $data );
		$private_key = $this->getSignKeyFromPfx ( $cert_path, $cert_pwd);
		openssl_private_decrypt ( $data, $crypted, $private_key );
		return $crypted;
	}

	private function getSignKeyFromPfx($certPath, $certPwd){
		$pkcs12certdata = file_get_contents ( $certPath );
		if($pkcs12certdata === false ){
			throw new Exception(  "file_get_contents fail。");
		}
		if(openssl_pkcs12_read ( $pkcs12certdata, $certs, $certPwd ) == FALSE ){
			throw new Exception($certPath . ", pwd[" . $certPwd . "] openssl_pkcs12_read fail。");
		}
		return $certs ['pkey'];
	}
}