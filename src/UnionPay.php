<?php
namespace zhangv\unionpay;

use \Exception;
use zhangv\unionpay\util\HttpClient;

/**
 * 银联支付
 * @license MIT
 * @author zhangv
 *
 * @method static service\App              App(array $config,string $mode)
 * @method static service\B2B              B2B(array $config,string $mode)
 * @method static service\B2C              B2C(array $config,string $mode)
 * @method static service\Direct           Direct(array $config,string $mode)
 * @method static service\DirectDebit      DirectDebit(array $config,string $mode)
 * @method static service\Token            Token(array $config,string $mode)
 * @method static service\Qrcode           Qrcode(array $config,string $mode)
 * @method static service\Wap              Wap(array $config,string $mode)
 * @method static service\Charge           Charge(array $config,string $mode)
 */

class UnionPay {
	const MODE_TEST = 'test', MODE_PROD = 'prod';
	const VERSION_500 = '5.0.0', VERSION_510 = '5.1.0';
	const SIGNMETHOD_RSA = '01', SIGNMETHOD_SHA256 = '11', SIGNMETHOD_SM3 = '12';
	const CHANNELTYPE_PC = '07', CHANNELTYPE_MOBILE = '08';
	const
		TXNTYPE_QUERY   = '00', //查询交易
		TXNTYPE_CONSUME = '01', //消费
		TXNTYPE_PREAUTH = '02', //预授权
		TXNTYPE_PREAUTHFINISH = '03', //预授权完成
		TXNTYPE_REFUND  = '04', //退货
		TXNTYPE_LOAD    = '05', //圈存
		TXNTYPE_DIRECTDEBIT = '11', //代收
		TXNTYPE_DIRECTDEPOSIT = '12', //代付
		TXNTYPE_PAYBILL = '13', //账单支付
		TXNTYPE_TRANSFER = '14', //转账
		TXNTYPE_BATCHDEBIT = '21', //批量交易
		TXNTYPE_QUERYBATCHDEBIT = '22', //批量查询
		TXNTYPE_CONSUMEUNDO = '31', //消费撤销
		TXNTYPE_PREAUTHUNDO = '32', //预授权撤销
		TXNTYPE_PREAUTHFINISHUNDO = '33', //预授权完成撤销
		TXNTYPE_QUERYBALANCE = '71',//余额查询
		TXNTYPE_AUTHORIZE = '72', //实名认证-建立绑定关系
		TXNTYPE_QUERYBILL = '73', //账单查询
		TXNTYPE_UNAUTHORIZE = '74', //解除绑定关系
		TXNTYPE_QUERYBIND = '75', //查询绑定关系
		TXNTYPE_FILEDOWNLOAD = '76',
		TXNTYPE_AUTHENTICATE = '77', //发送短信验证码交易
		TXNTYPE_DIRECTOPEN = '79',
		TXNTYPE_QUERYOPEN = '78', //开通查询交易
		TXNTYPE_APPLYTOKEN = '79', //开通交易
		TXNTYPE_DELETETOKEN = '74',
		TXNTYPE_UPDATETOKEN = '79',
		TXNTYPE_ICCARD = '94', //IC卡脚本通知
		TXNTYPE_UPDATEPUBLICKEY = '95'; //查询更新加密公钥证书
	const
		BIZTYPE_B2C     = '000201', //B2C网关支付
		BIZTYPE_B2B     = '000202', //B2B
		BIZTYPE_DIRECT  = '000301', //认证支付（无跳转标准版）
		BIZTYPE_GRADE   = '000302', //评级支付
		BIZTYPE_DIRECTDEPOSIT   = '000401', //代付
		BIZTYPE_DIRECTDEBIT     = '000501', //代收
		BIZTYPE_CHARGE  = '000601', //账单支付
		BIZTYPE_AQUIRE  = '000801', //跨行收单
		BIZTYPE_APPLEPAY  = '000802', //ApplePay
		BIZTYPE_BIND    = '000901', //绑定支付
		BIZTYPE_TOKEN   = '000902', //Token支付（无跳转token版）
		BIZTYPE_ORDER   = '001001', //订购
		BIZTYPE_DEFAULT = '000000'; //默认值
	const
		ACCESSTYPE_MERCHANT = '0', //商户直连接入
		ACCESSTYPE_ACQUIRER = '1', //收单机构接入
		ACCESSTYPE_PLATFORM = '2'; //平台商户接入
	const
		RESPCODE_SUCCESS = '00', RESPCODE_SIGNATURE_VERIFICATION_FAIL = '11';
	const SMSTYPE_OPEN = '00', SMSTYPE_PAY = '02', SMSTYPE_PREAUTH = '04', SMSTYPE_OTHER = '05';

	protected $txnmap = [//This fucking map is killing me.
		self::BIZTYPE_DIRECTDEBIT =>[
			self::TXNTYPE_AUTHORIZE => ['11', '01', '10'],
			self::TXNTYPE_UNAUTHORIZE => ['04', '00'],
			self::TXNTYPE_DIRECTDEBIT => ['00'],
			self::TXNTYPE_CONSUMEUNDO => ['00'],
			self::TXNTYPE_REFUND => ['00'],
			self::TXNTYPE_QUERYBIND => ['00'],
			self::TXNTYPE_AUTHENTICATE => ['01'],
			self::TXNTYPE_BATCHDEBIT => ['02'],
			self::TXNTYPE_QUERYBATCHDEBIT=> ['02']
		],
		self::BIZTYPE_B2C => [
			self::TXNTYPE_CONSUME => ['01'],
			self::TXNTYPE_CONSUMEUNDO => ['00'],
			self::TXNTYPE_REFUND => ['00'],
			self::TXNTYPE_QUERY => ['00'],
			self::TXNTYPE_FILEDOWNLOAD => ['01'],
			self::TXNTYPE_PREAUTH => ['01'],
			self::TXNTYPE_PREAUTHUNDO => ['00'],
			self::TXNTYPE_PREAUTHFINISH => ['00'],
			self::TXNTYPE_PREAUTHFINISHUNDO => ['00'],
			self::TXNTYPE_UPDATEPUBLICKEY => ['00']
		],
		self::BIZTYPE_DIRECT => [
			self::TXNTYPE_DIRECTOPEN => ['00'],
			self::TXNTYPE_QUERYOPEN => ['00'],
			self::TXNTYPE_CONSUME => ['01', '03'],
			self::TXNTYPE_AUTHENTICATE => ['00', '02', '04', '05'], //sms
			self::TXNTYPE_CONSUMEUNDO => ['00'],
			self::TXNTYPE_REFUND => ['00'],
		],
		self::BIZTYPE_TOKEN => [//extends Direct
			self::TXNTYPE_APPLYTOKEN => ['05', '03'],
			self::TXNTYPE_DELETETOKEN => ['01'],
		],
		self::BIZTYPE_DEFAULT => [//extends B2C
			self::TXNTYPE_CONSUME => ['06'],
		],
		self::BIZTYPE_CHARGE => [
			self::TXNTYPE_PAYBILL => [
				'01', //bill
				'02' //tax
			]
		]
	];

	protected $apiEndpoint = "https://gateway.95516.com/";

	protected $frontTransUrl = "gateway/api/frontTransReq.do";
	protected $backTransUrl = "gateway/api/backTransReq.do";
	protected $batchTransUrl = "gateway/api/batchTrans.do";
	protected $singleQueryUrl = "gateway/api/queryTrans.do";
	protected $cardTransUrl = "gateway/api/cardTransReq.do";
	protected $appTransUrl = "gateway/api/appTransReq.do";

	protected $jfFrontTransUrl = "jiaofei/api/frontTransReq.do";
	protected $jfBackTransUrl = "jiaofei/api/backTransReq.do";
	protected $jfSingleQueryUrl = "jiaofei/api/queryTrans.do";
	protected $jfCardTransUrl = "jiaofei/api/cardTransReq.do";
	protected $jfAppTransUrl = "jiaofei/api/appTransReq.do";

	protected $fileDownloadUrl = "https://filedownload.95516.com/";

	public $response;
	public $responseArray;
	public $respCode, $respMsg;
	public static $verifyCerts510 = [];
	public static $verifyPublicKeys = [];
	public static $signCerts = [];
	public static $encryptCerts = [];
	/** @var array 支付配置 */
	protected $config = [];
	/** @var string */
	protected $mode = UnionPay::MODE_PROD;
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
    <form id="payform" name="payform" action="%s" method="post">
        %s
        <button>提交</button>
    </form>
    <script type="text/javascript">
        document.onreadystatechange = function(){
            if(document.readyState == "complete") {
                document.payform.submit();
            }
        };
    </script>
</body>
</html>
HTML;

	public function __construct($config, $mode = UnionPay::MODE_PROD) {
		$this->config = $config;
		$this->mode = $mode;
		$this->httpClient = new HttpClient(3);
		if ($mode == UnionPay::MODE_TEST) {
			$this->apiEndpoint = 'https://gateway.test.95516.com/';
			$this->fileDownloadUrl = 'https://filedownload.test.95516.com/';
		}
	}

	/**
	 * @param string $name
	 * @param string $config
	 * @param string $mode
	 * @return mixed
	 */
	private static function load($name, $config, $mode = self::MODE_PROD) {
		$service = __NAMESPACE__ . "\\service\\{$name}";
		return new $service($config, $mode);
	}

	/**
	 * @param string $name
	 * @param array  $arguments
	 *
	 * @return mixed
	 */
	public static function __callStatic($name, $arguments) {
		return self::load($name, ...$arguments);
	}

	public function setHttpClient($httpClient) {
		$this->httpClient = $httpClient;
	}

	public function setConfig($config){
		$this->config = $config;
	}

	/**
	 * @param string $url
	 * @param array $params
	 * @param bool $validateResp
	 * @return array
	 * @throws \Exception
	 */
	protected function post($url, $params, $validateResp = true) {
		$postbody = $this->getRequestParamString($params);
		$headers = array('Content-type:application/x-www-form-urlencoded;charset=UTF-8');
		$opts = array(
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSLVERSION => 1
		);
		if($url !== $this->fileDownloadUrl) $url = $this->apiEndpoint . $url;

		$this->response = $this->httpClient->post($url, $postbody, $headers, $opts);
		if (!$this->response || $this->response == '') {
			throw new Exception("No response from remote host");
		}

		if($this->response == 'Invalid request.'){
			throw new Exception("Invalid request, body = {$postbody}");
		}

		$this->responseArray = $this->convertQueryStringToArray($this->response);
		if (empty($this->responseArray['respCode'])) {
			throw new Exception("Response error - {$this->response}, request: {$postbody}");
		}

		$this->respCode = $this->responseArray['respCode'];
		$this->respMsg = (!empty($this->responseArray['respMsg']))?$this->responseArray['respMsg']:null;
		if ($this->respCode == UnionPay::RESPCODE_SUCCESS) {
			if ($validateResp === true && $this->validateSign($this->responseArray) !== true) {
				throw new \Exception("Signature verification failed, response: {$this->response}");
			}else {
				return $this->responseArray;
			}
		}else {
			throw new \Exception("{$this->respMsg} - request: $postbody , response: {$this->response}",$this->respCode);
		}
	}protected function get($url,$params = []) {
		return $this->httpClient->get($url, $params);
	}

	/**
	 * No need to create an auto submitting form
	 * @param $url
	 * @param array $params
	 * @return mixed
	 */
	protected function submitForm($url,$params = []) {
		$postbody = $this->getRequestParamString($params);

		$headers = array('Content-type:application/x-www-form-urlencoded;charset=UTF-8');
		$opts = array(
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSLVERSION => 1
		);
		$url = $this->apiEndpoint . $url;
		return $this->httpClient->post($url, $postbody, $headers, $opts);
	}

	public function convertQueryStringToArray($str, $urldecode = false) {
		$result = array();
		$len = strlen($str);
		$temp = "";
		$key = "";
		$isKey = true;
		$isOpen = false;
		$openName = "\0";

		for ($i = 0; $i < $len; $i++) {
			$curChar = $str[$i];
			if ($isOpen) {
				if ($curChar == $openName) {
					$isOpen = false;
				}
				$temp .= $curChar;
			} elseif ($curChar == "{") {
				$isOpen = true;
				$openName = "}";
				$temp .= $curChar;
			} elseif ($curChar == "[") {
				$isOpen = true;
				$openName = "]";
				$temp .= $curChar;
			} elseif ($isKey && $curChar == "=") {
				$key = $temp;
				$temp = "";
				$isKey = false;
			} elseif ($curChar == "&" && !$isOpen) {
				$this->putKeyValueToDictionary($temp, $isKey, $key, $result, $urldecode);
				$temp = "";
				$isKey = true;
			} else {
				$temp .= $curChar;
			}
		}
		$this->putKeyValueToDictionary($temp, $isKey, $key, $result, $urldecode);
		return $result;
	}

	private function putKeyValueToDictionary($temp, $isKey, $key, &$result, $needUrlDecode) {
		if ($isKey) {
			$key = $temp;
			if (strlen($key) == 0) {
				return false;
			}
			$result [$key] = "";
		}else {
			if (strlen($key) == 0) {
				return false;
			}
			if ($needUrlDecode) {
				$result [$key] = urldecode($temp);
			}else {
				$result [$key] = $temp;
			}
		}
	}

	/**
	 * 组装报文
	 *
	 * @param array $params
	 * @return string
	 */
	protected function getRequestParamString($params) {
		$params_str = '';
		foreach ($params as $key => $value) {
			if (trim($value) == '') {
				continue;
			}
			$params_str .= ($key . '=' . (!isset ($value) ? '' : urlencode($value)) . '&');
		}
		return substr($params_str, 0, strlen($params_str) - 1);
	}

	/**
	 * 取签名证书ID(SN)
	 * @return string
	 */
	public function getSignCertId() {
		return $this->getCertIdPfx($this->config['signCertPath']);
	}

	/**
	 * 取签名证书ID(SN)
	 * @return string
	 */
	protected function getEncryptCertId() {
		return $this->getCertIdPfx($this->config['signCertPath']);
	}

	/**
	 * 取.pfx格式证书ID(SN)
	 * @return string
	 */
	protected function getCertIdPfx($path) {
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
	protected function getCertIdCer($path) {
		$x509data = file_get_contents($path);
		openssl_x509_read($x509data);
		$certdata = openssl_x509_parse($x509data);
		return $certdata['serialNumber'];
	}

	/**
	 * 构建自动提交HTML表单
	 * @param array $params
	 * @param string $title
	 * @param string $url
	 * @param bool $submit
	 * @return string
	 */
	protected function createPostForm($params, $title = '支付', $url = null, $submit = false) {
		if($submit === true){
			return $this->submitForm($url?:$this->frontTransUrl,$params);
		}
		$input = '';
		foreach ($params as $key => $item) {
			if (trim($item) == '') {
				continue;
			}
			$input .= "\t\t<input type=\"hidden\" name=\"{$key}\" value=\"{$item}\">\n";
		}
		if (!$url) {
			$url = $this->apiEndpoint . $this->frontTransUrl;
		}
		return sprintf($this->formTemplate, $title, $url, $input);
	}

	/**
	 * 签名数据
	 * @param array $params
	 * @param string $signMethod
	 * @throws \Exception
	 * @return string|bool
	 */
	public function sign($params, $signMethod = UnionPay::SIGNMETHOD_RSA) {
		$signData = $params;
		if (empty($signData['certId']) && $signMethod == UnionPay::SIGNMETHOD_RSA) {//RSA用证书
			$signData['certId'] = $this->getSignCertId();
		}
		ksort($signData);
		$signQueryString = $this->arrayToString($signData, true);
		if ($signMethod == UnionPay::SIGNMETHOD_RSA) {
			if ($params['version'] == self::VERSION_500) {
				$datasha1 = sha1($signQueryString);
				$signed = $this->rsaSign($datasha1);
				return $signed;
			} elseif ($params['version'] == self::VERSION_510) {
				$sha256 = hash('sha256', $signQueryString);
				$privateKey = $this->getSignPrivateKey();
				$result = openssl_sign($sha256, $signature, $privateKey, OPENSSL_ALGO_SHA256);
				if ($result) {
					$signature_base64 = base64_encode($signature);
					return $signature_base64;
				} else {
					throw new \Exception("Error while signing");
				}
			} else {
				throw new \Exception("Unsupported version - {$params['version']}");
			}
		}elseif($signMethod == UnionPay::SIGNMETHOD_SHA256){
			$params_str = $signQueryString;
			$params_before_sha256 = hash('sha256', $this->config['secureKey']);
			$params_before_sha256 = $params_str.'&'.$params_before_sha256;
			$params_after_sha256 = hash('sha256',$params_before_sha256);
			return $params_after_sha256;
		}else {
			throw new \Exception("Unsupported Sign Method - {$signMethod}");
		}
	}

	/**
	 * 数组转换成字符串
	 * @param array $arr
	 * @param boolean $sort
	 * @return string
	 */
	protected function arrayToString($arr, $sort = false) {
		$str = '';
		$para = $arr;
		if ($sort) {
			ksort($para);
			reset($para);
		}
		foreach ($para as $key => $value) {
			if (trim($value) == '') {
				continue;
			}
			$str .= $key . '=' . $value . '&';
		}
		return substr($str, 0, strlen($str) - 1);
	}

	/**
	 * RSA签名数据，并base64编码
	 * @param string $data 待签名数据
	 * @return mixed
	 */
	protected function rsaSign($data) {
		$privatekey = $this->getSignPrivateKey();
		$result = openssl_sign($data, $signature, $privatekey);
		if ($result) {
			return base64_encode($signature);
		}
		return false;
	}

	/**
	 * 签名证书私钥
	 * @return resource
	 */
	protected function getSignPrivateKey() {
		$pkcs12 = file_get_contents($this->config['signCertPath']);
		openssl_pkcs12_read($pkcs12, $certs, $this->config['signCertPwd']);
		return $certs['pkey'];
	}

	/**
	 * 验证签名
	 * @throws \Exception
	 * @return bool
	 */
	public function validateSign($params) {
		if ($params['signMethod'] == UnionPay::SIGNMETHOD_RSA) {
			$signaturebase64 = $params['signature'];
			$verifyArr = $params;
			unset($verifyArr['signature']);
			ksort($verifyArr);
			$verifyStr = $this->arrayToString($verifyArr);

			if ($params['version'] == self::VERSION_500) {
				$certId = $params['certId'];
				$publicKey = $this->getVerifyPublicKey($certId);
				$verifySha1 = sha1($verifyStr, FALSE);
				$signature = base64_decode($signaturebase64);
				$result = openssl_verify($verifySha1, $signature, $publicKey, OPENSSL_ALGO_SHA1);
				if ($result === -1) {
					throw new \Exception('Verify Error:' . openssl_error_string());
				}
				return ($result === 1)?true:false;
			} elseif ($params['version'] == self::VERSION_510) {
				$signPubKeyCert = $params['signPubKeyCert'];
				$cert = $this->verifyAndGetVerifyCert($signPubKeyCert);

				if ($cert == null) {
					return false;
				}else {
					$verifySha256 = hash('sha256', $verifyStr);
					$signature = base64_decode($signaturebase64);
					$result = openssl_verify($verifySha256, $signature, $cert, OPENSSL_ALGO_SHA256);
					if ($result === -1) {
						throw new \Exception('Verify Error:' . openssl_error_string());
					}
					return ($result === 1)?true:false;
				}
			}else {
				throw new \Exception("Unsupported version {$params['version']}");
			}
		}else {
			return $this->validateBySecureKey($params, $this->config['secureKey']);
		}
	}

	/**
	 * 检查返回结果中的公钥证书是否有效
	 * @param string $certBase64String
	 * @return mixed|null
	 * @throws Exception
	 */
	public function verifyAndGetVerifyCert($certBase64String) {
		if (array_key_exists($certBase64String, UnionPay::$verifyCerts510)) {
			return UnionPay::$verifyCerts510[$certBase64String];
		}

		if (trim($this->config['verifyRootCertPath']) == '' || trim($this->config['verifyMiddleCertPath']) == '') {
			throw new \Exception("Root certificate and middle certificate should be configured");
		}
		openssl_x509_read($certBase64String);
		$certInfo = openssl_x509_parse($certBase64String);

		$cn = $this->getIdentitiesFromCertficate($certInfo);
		if ($this->config['ifValidateCNName'] === true) {
			if ("中国银联股份有限公司" != $cn) {
				return null;
			}
		} elseif ("中国银联股份有限公司" != $cn && "00040000:SIGN" != $cn) {
			return null;
		}

		$from = date_create('@' . $certInfo ['validFrom_time_t']);
		$to = date_create('@' . $certInfo ['validTo_time_t']);
		$now = date_create(date('Ymd'));
		$interval1 = $from->diff($now);
		$interval2 = $now->diff($to);
		if ($interval1->invert || $interval2->invert) {
			throw new \Exception("Public key certificate expired");
		}
		$result = openssl_x509_checkpurpose($certBase64String, X509_PURPOSE_ANY,
			array(
				$this->config['verifyRootCertPath'],
				$this->config['verifyMiddleCertPath']
			)
		);
		if ($result === FALSE) {
			return null;
		} else if ($result === TRUE) {
			UnionPay::$verifyCerts510[$certBase64String] = $certBase64String;
			return UnionPay::$verifyCerts510[$certBase64String];
		} else {
			throw new \Exception("validate signPubKeyCert by rootCert failed with error");
		}
	}

	protected function getIdentitiesFromCertficate($certInfo) {
		$cn = $certInfo['subject'];
		$cn = $cn['CN'];
		$company = explode('@', $cn);
		if (count($company) < 3) {
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
	protected function getVerifyPublicKey($certId) {
		if (isset(self::$verifyPublicKeys[$certId])) {
			return self::$verifyPublicKeys[$certId];
		}
		$pubkeys = $this->getVerifyPublicKeyByCerts([
			$this->config['verifyCertPath'],
			$this->config['verifyRootCertPath'],
			$this->config['verifyMiddleCertPath'],
			$this->config['encryptCertPath'],
			$this->config['signCertPath'],
		]);
		if (!isset($pubkeys[$certId])) {
			throw new \Exception("Public key not found with certificate id ($certId), existing ones " . implode(',', array_keys($pubkeys)));
		}
		return $pubkeys[$certId];
	}

	protected function getVerifyPublicKeyByCerts(array $paths) {
		foreach ($paths as $path) {
			$x509data = file_get_contents($path);
			if(strrpos($path,'.pfx') == strlen($path) - strlen('.pfx')){

				openssl_pkcs12_read($x509data, $certs, $this->config['signCertPwd']);
				$x509data = $certs['cert'];
			}
			openssl_x509_read($x509data);
			$certdata = openssl_x509_parse($x509data);
			$sn = $certdata['serialNumber'];
			if (empty(self::$verifyPublicKeys[$sn])) {
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
		if ($params['signMethod'] == UnionPay::SIGNMETHOD_SHA256) {
			$sha256secureKey = hash('sha256', $secureKey);
			$params_before_sha256 = $verifyStr . '&' . $sha256secureKey;
			$params_after_sha256 = hash('sha256', $params_before_sha256);
			return $params_after_sha256 == $signature;
		} else {
			throw new \Exception("Unsupported signmethod - {$params['signMethod']}");
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
		$public_key = self::getEncryptKey($cert_path);
		openssl_public_encrypt($data, $crypted, $public_key);
		return base64_encode($crypted);
	}

	public static function getEncryptKey($cert_path){
		if(!array_key_exists($cert_path, self::$encryptCerts)){
			self::initEncryptCert($cert_path);
		}
		if(array_key_exists($cert_path, self::$encryptCerts)){
			return self::$encryptCerts[$cert_path]->key;
		}
		return false;
	}

	private static function initEncryptCert($cert_path) {
		$x509data = file_get_contents ( $cert_path );
		if($x509data === false ){
			throw new Exception("Fail reading encrypt certificate from $cert_path");
		}

		if(!openssl_x509_read ( $x509data )){
			throw new Exception("$cert_path openssl_x509_read fail");
		}

		$cert = new \stdClass();
		$certdata = openssl_x509_parse ( $x509data );
		$cert->certId = $certdata ['serialNumber'];
		$cert->key = $x509data;
		self::$encryptCerts[$cert_path] = $cert;
	}

	/**
	 * 解密数据
	 * @param string $data
	 * @return string
	 * @throws Exception
	 */
	public function decryptData($data) {
		$cert_path = $this->config['signCertPath'];
		$cert_pwd = $this->config['signCertPwd'];
		$data = base64_decode($data);
		$private_key = $this->getSignKeyFromPfx($cert_path, $cert_pwd);
		openssl_private_decrypt($data, $decrypted, $private_key);
		return $decrypted;
	}

	private function getSignKeyFromPfx($certPath, $certPwd) {
		if (!array_key_exists($certPath, self::$signCerts)) {
			self::initSignCert($certPath, $certPwd);
		}
		return self::$signCerts[$certPath]->key;
	}

	private static function initSignCert($certPath, $certPwd){
		$pkcs12certdata = file_get_contents ( $certPath );
		if($pkcs12certdata === false ){
			throw new Exception("file_get_contents fail。");
		}

		if(openssl_pkcs12_read ( $pkcs12certdata, $certs, $certPwd ) === false ){
			throw new Exception($certPath . ", pwd[" . $certPwd . "] openssl_pkcs12_read fail。");
		}

		$cert = new \stdClass();
		$x509data = $certs ['cert'];

		if(!openssl_x509_read ( $x509data )){
			throw new Exception($certPath . ", pwd[" . $certPwd . "] openssl_x509_read fail。");
		}
		$certdata = openssl_x509_parse ( $x509data );
		$cert->certId = $certdata ['serialNumber'];
		$cert->key = $certs ['pkey'];
		$cert->cert = $x509data;

		self::$signCerts[$certPath] = $cert;
	}

	protected function getCustomerInfo($customerInfo) {
		if ($customerInfo == null || count($customerInfo) == 0) {
					return "";
		}
		return base64_encode("{" . $this->arrayToString($customerInfo, false) . "}");
	}

	/**
	 * Encrypt the customer information
	 * @param array $customerInfo
	 * @return string
	 */
	protected function encryptCustomerInfo($customerInfo) {
		if ($customerInfo == null || count($customerInfo) == 0) {
					return "";
		}
		$sensitive = ['phoneNo', 'cvn2', 'expired']; //'certifTp' certifId ??
		$sensitiveInfo = array();
		foreach ($customerInfo as $key => $value) {
			if (in_array($key, $sensitive)) {
				$sensitiveInfo [$key] = $customerInfo [$key];
				unset ($customerInfo [$key]);
			}
		}
		if (count($sensitiveInfo) > 0) {
			$sensitiveInfoStr = $this->arrayToString($sensitiveInfo, true);
			$encryptedInfo = $this->encryptData($sensitiveInfoStr);
			$customerInfo ['encryptedInfo'] = $encryptedInfo;
		}
		return base64_encode("{" . $this->arrayToString($customerInfo) . "}");
	}

	protected function encodeFileContent($file) {
		if(file_exists($file)){
			$file_content = file_get_contents($file);
		}else $file_content = $file;
		//UTF8 去掉文本中的 bom头
		$BOM = chr(239) . chr(187) . chr(191);
		$file_content = str_replace($BOM, '', $file_content);
		$file_content_deflate = gzcompress($file_content);
		$file_content_base64 = base64_encode($file_content_deflate);
		return $file_content_base64;
	}

	/**
	 * 通用异步通知处理
	 * @param array $notifyData
	 * @param callable $callback
	 * @param bool $validate
	 * @return mixed
	 * @throws \Exception
	 */
	public function onNotify(array $notifyData, $callback, bool $validate = true) {
		if($validate === true && $this->validateSign($notifyData) !== true) throw new \Exception('Invalid notify data, ' . print_r($notifyData,true));
		if (is_callable($callback)) {
			return call_user_func_array($callback, [$notifyData]);
		}else {
			throw new Exception("The callback($callback) must be callable.");
		}
	}

	/**
	 * 支付异步通知处理
	 * @param array $notifyData
	 * @param callable $callback
	 * @param bool $validate
	 * @return mixed
	 * @throws \Exception
	 */
	protected function onPayNotify(array $notifyData, callable $callback, bool $validate = true) {
		$respCode = $notifyData['respCode'];
		if ($respCode == '00') {
			$txnType = isset($notifyData['txnType'])?$notifyData['txnType']:null;
			if ($txnType == UnionPay::TXNTYPE_CONSUME) {
				return $this->onNotify($notifyData,$callback,$validate);
			}else{
				throw new Exception("Invalid txnType({$txnType}), expecting ".UnionPay::TXNTYPE_CONSUME);
			}
		}
	}

	/**
	 * 退款异步通知处理
	 * @param array $notifyData
	 * @param callable $callback
	 * @param bool $validate
	 * @return mixed
	 * @throws \Exception
	 */
	protected function onRefundNotify(array $notifyData, callable $callback, bool $validate = true) {
		$respCode = $notifyData['respCode'];
		if ($respCode == '00') {
			$txnType = isset($notifyData['txnType'])?$notifyData['txnType']:null;
			if ($txnType == UnionPay::TXNTYPE_REFUND) {
				return $this->onNotify($notifyData,$callback,$validate);
			}else{
				throw new Exception("Invalid txnType({$txnType}), expecting ".UnionPay::TXNTYPE_REFUND);
			}
		}
	}

	/**
	 * 消费撤销异步通知处理
	 * @param array $notifyData
	 * @param callable $callback
	 * @param bool $validate
	 * @return mixed
	 * @throws \Exception
	 */
	protected function onPayUndoNotify(array $notifyData, callable $callback, bool $validate = true) {
		$respCode = $notifyData['respCode'];
		if ($respCode == '00') {
			$txnType = isset($notifyData['txnType'])?$notifyData['txnType']:null;
			if ($txnType == UnionPay::TXNTYPE_CONSUMEUNDO) {
				return $this->onNotify($notifyData,$callback,$validate);
			}else{
				throw new Exception("Invalid txnType({$txnType}), expecting ".UnionPay::TXNTYPE_CONSUMEUNDO);
			}
		}
	}

	/**
	 * 无跳转开通异步通知处理
	 * @param array $notifyData
	 * @param callable $callback
	 * @param bool $validate
	 * @return mixed
	 * @throws \Exception
	 */
	protected function onOpenNotify(array $notifyData, callable $callback, bool $validate = true) {
		$respCode = $notifyData['respCode'];
		if ($respCode == '00') {
			$txnType = isset($notifyData['txnType'])?$notifyData['txnType']:null;
			if ($txnType == UnionPay::TXNTYPE_DIRECTOPEN) {
				return $this->onNotify($notifyData,$callback,$validate);
			}else{
				throw new Exception("Invalid txnType({$txnType}), expecting ".UnionPay::TXNTYPE_DIRECTOPEN);
			}
		}
	}


	/**
	 * 加密公钥更新查询
	 * @param string $orderId
	 * @param array $ext
	 * @return mixed
	 */
	public function updatePublicKey($orderId, $ext = []) {
		$params = array_merge($this->commonParams(),[
			'txnType' => UnionPay::TXNTYPE_UPDATEPUBLICKEY,
			'txnSubType' => '00',
			'bizType' => '000000',
			'accessType' => UnionPay::ACCESSTYPE_MERCHANT,
			'channelType' => UnionPay::CHANNELTYPE_PC,
			'certType' => '01',
			'orderId' => $orderId,
			'txnTime' => date('YmdHis'),
		],$ext);
		$params['signature'] = $this->sign($params);
		$result = $this->post($this->backTransUrl, $params);
		return $result;
	}

	/**
	 * 交易状态查询
	 * @param string $orderId
	 * @param string $txnTime
	 * @param array $ext
	 * @return mixed
	 */
	public function query($orderId, $txnTime, $ext = []) {
		$params = array_merge($this->commonParams(),[
			'txnType' => UnionPay::TXNTYPE_QUERY,
			'txnSubType' => '00',
			'bizType' => '000000',
			'accessType' => UnionPay::ACCESSTYPE_MERCHANT,
			'channelType' => UnionPay::CHANNELTYPE_PC,

			'orderId' => $orderId,
			'txnTime' => $txnTime
		],$ext);
		$params['signature'] = $this->sign($params);
		return $this->post($this->singleQueryUrl, $params, false);
	}

	/**
	 * 文件传输
	 * @param string $settleDate MMDD
	 * @param string $fileType
	 * @return mixed
	 */
	public function fileDownload($settleDate, $fileType = '00') {
		$params = array_merge($this->commonParams(),[
			'txnType' => UnionPay::TXNTYPE_FILEDOWNLOAD,
			'txnSubType' => '01',
			'bizType' => '000000',
			'accessType' => UnionPay::ACCESSTYPE_MERCHANT,
			'settleDate' => $settleDate, //'0119', MMDD
			'txnTime' => date('YmdHis'),
			'fileType' => $fileType,
		]);
		$params['signature'] = $this->sign($params);
		$result = $this->post($this->fileDownloadUrl, $params, false);
		return $result;
	}

	/**
	 * 通用配置参数
	 * @return array
	 */
	protected function commonParams() {
		return [
			'version' => $this->config['version'],
			'signMethod' =>  $this->config['signMethod'],
			'encoding' => $this->config['encoding'],
			'encryptCertId' => $this->getCertIdCer($this->config['encryptCertPath']),
			'backUrl' => $this->config['notifyUrl'],
			'merId' => $this->config['merId'],
			'certId' => $this->getSignCertId()
		];
	}

}