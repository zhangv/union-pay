<?php
class UnionPay {
	const URL_FRONTTRANS = "https://gateway.95516.com/gateway/api/frontTransReq.do";
	const URL_BACKTRANS = "https://gateway.95516.com/gateway/api/backTransReq.do";
	const URL_BATCHTRANS = "https://gateway.95516.com/gateway/api/batchTrans.do";
	const URL_SINGLEQUERY = "https://gateway.95516.com/gateway/api/queryTrans.do";
	const URL_FILEQUERY = "https://filedownload.95516.com/";
	const URL_CARDREQUEST = "https://gateway.95516.com/gateway/api/cardTransReq.do";
	const URL_APPREQUEST = "https://gateway.95516.com/gateway/api/appTransReq.do";

	/**
	 * 支付配置
	 *
	 * returnUrl        前台返回地址
	 * notifyUrl        后台通知地址
	 * merId            商户号
	 * signCertPath     签名证书路径
	 * signCertPwd      签名证书密码
	 * verifyCertPath   验签证书路径
	 */
	public $config = [];
	/**
	 * 支付参数，提交到银联对应接口的所有参数
	 * @var array
	 */
	public $params = [];
	/**
	 * 自动提交表单模板
	 * @var string
	 */
	private $formTemplate = <<<'HTML'
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
	}

	public function pay($orderId,$amt,$desc,$ext){
		$this->params = [
			'version' => '5.0.0', //版本号
			'encoding' => 'UTF-8', //编码方式
			'certId' => $this->getSignCertId(), //证书ID
			'signMethod' => '01', //签名方式
			'txnType' => '01', //交易类型
			'txnSubType' => '01', //交易子类
			'bizType' => '000201', //产品类型
			'channelType' => '08',//渠道类型 08-手机网页支付 07-网关支付
			'frontUrl' => $this->config['notifyUrl'], //前台通知地址
			'backUrl' => $this->config['returnUrl'], //后台通知地址
//			'frontFailUrl' => $config['failUrl'], //失败交易前台跳转地址
			'accessType' => '0', //接入类型
			'merId' => $this->config['merId'], //商户代码
			'orderId' => $orderId, //商户订单号 不能含“-”或“_”
			'txnTime' => date('YmdHis'), //订单发送时间
			'txnAmt' => $amt , //交易金额，单位分
			'currencyCode' => '156', //交易币种
			'defaultPayType' => '0001',	//默认支付方式
			//'orderDesc' => $desc, //订单描述，网关支付和wap支付暂时不起作用
//			'customerIp' => Ip::remote_addr(), //填写持卡人发起交易的IP地址，用于防钓鱼
			'reqReserved' => $ext,//	商户自定义保留域，交易应答时会原样返回
//			'orderTimeout' => 86400000 //订单接收超时时间 单位为毫秒，交易发生时，该笔交易在银联全渠道系统中有效的最长时间。当距离交易发送时间超过该时间时，银联全渠道系统不再为该笔交易提供支付服务
		];
		return $this->createPostForm();
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
	private function getCertIdPfx($path){
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
	public function createPostForm(){
		$this->params['signature'] = $this->sign();
		$input = '';
		foreach($this->params as $key => $item) {
			$input .= "\t\t<input type=\"hidden\" name=\"{$key}\" value=\"{$item}\">\n";
		}
		return sprintf($this->formTemplate, $this->config['frontUrl'], $input);
	}

	/**
	 * 签名数据
	 * 签名规则:
	 * 除signature域之外的所有项目都必须参加签名
	 * 根据key值按照字典排序，然后用&拼接key=value形式待签名字符串；
	 * 然后对待签名字符串使用sha1算法做摘要；
	 * 用银联颁发的私钥对摘要做RSA签名操作
	 * 签名结果用base64编码后放在signature域
	 *
	 * @throws \InvalidArgumentException
	 * @return multitype|string
	 */
	private function sign() {
		$signData = $this->filterBeforSign();
		ksort($signData);
		$signQueryString = $this->arrayToString($signData);
		if($this->params['signMethod'] == 01) {
			//签名之前先用sha1处理
			//echo $signQueryString;exit;
			$datasha1 = sha1($signQueryString);
			$signed = $this->rsaSign($datasha1);
		} else {
			throw new \InvalidArgumentException('Nonsupport Sign Method');
		}
		return $signed;
	}

	/**
	 * 过滤待签名数据
	 * signature域不参加签名
	 *
	 * @return array
	 */
	private function filterBeforSign(){
		$tmp = $this->params;
		unset($tmp['signature']);
		return $tmp;
	}

	/**
	 * 数组转换成字符串
	 * @param array $arr
	 * @return string
	 */
	private function arrayToString($arr){
		$str = '';
		foreach($arr as $key => $value) {
			$str .= $key.'='.$value.'&';
		}
		return substr($str, 0, strlen($str) - 1);
	}

	/**
	 * RSA签名数据，并base64编码
	 * @param string $data 待签名数据
	 * @return mixed
	 */
	private function rsaSign($data){
		$privatekey = $this->getSignPrivateKey();
		$result = openssl_sign($data, $signature, $privatekey);
		if($result) {
			return base64_encode($signature);
		}
		return false;
	}

	/**
	 * 取签名证书私钥
	 * @return resource
	 */
	private function getSignPrivateKey(){
		$pkcs12 = file_get_contents($this->config['signCertPath']);
		openssl_pkcs12_read($pkcs12, $certs, $this->config['signCertPwd']);
		return $certs['pkey'];
	}

	/**
	 * 支付撤销
	 */
	public function payUndo(){}

	/**
	 * 退款
	 */
	public function refund($orderId,$origQryId,$refundAmt,$ext){
		$this->params = [
			'version' => '5.0.0',		//版本号
			'encoding' => 'utf-8',		//编码方式
			'certId' => $this->getSignCertId(),	//证书ID
			'signMethod' => '01',		//签名方法
			'txnType' => '04',		//交易类型 04- 退款
			'txnSubType' => '00',		//交易子类
			'bizType' => '000201',		//业务类型
			'accessType' => '0',		//接入类型
			'channelType' => '07',		//渠道类型
			'orderId' => $orderId,	//商户订单号，重新产生，不同于原消费
			'merId' => $this->config['merId'],	//商户代码，请修改为自己的商户号
			'origQryId' => $origQryId,    //原消费的queryId，可以从查询接口或者通知接口中获取
			'txnTime' => date('YmdHis'),	//订单发送时间，重新产生，不同于原消费
			'txnAmt' => $refundAmt,              //交易金额，退货总金额需要小于等于原消费
			'backUrl' => $this->config['notifyUrl'],	   //后台通知地址
			'reqReserved' => $ext, //请求方保留域，透传字段，查询、通知、对账文件中均会原样出现
		];
		$this->params['signature'] = $this->sign();
		$result = $this->sendHttpRequest($this->params,self::URL_BACKTRANS);
		return $result;
	}

	/**
	 * 后台交易 HttpClient通信
	 * @param unknown_type $params
	 * @param unknown_type $url
	 * @return mixed
	 */
	function sendHttpRequest($params, $url) {
		$opts = $this->getRequestParamString ( $params );

		$ch = curl_init ();
		curl_setopt ( $ch, CURLOPT_URL, $url );
		curl_setopt ( $ch, CURLOPT_POST, 1 );
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, false);//不验证证书
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, false);//不验证HOST
		curl_setopt ( $ch, CURLOPT_SSLVERSION, 3);
		curl_setopt ( $ch, CURLOPT_HTTPHEADER, array (
			'Content-type:application/x-www-form-urlencoded;charset=UTF-8'
		) );
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, $opts );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
		$html = curl_exec ( $ch );
		curl_close ( $ch );
		return $html;
	}

	/**
	 * 组装报文
	 *
	 * @param unknown_type $params
	 * @return string
	 */
	function getRequestParamString($params) {
		$params_str = '';
		foreach ( $params as $key => $value ) {
			$params_str .= ($key . '=' . (!isset ( $value ) ? '' : urlencode( $value )) . '&');
		}
		return substr ( $params_str, 0, strlen ( $params_str ) - 1 );
	}

	public function appPay(){
		$params = array(
				'version' => '5.0.0',				//版本号
				'encoding' => 'utf-8',				//编码方式
				'certId' => getSignCertId (),			//证书ID
				'txnType' => '01',				//交易类型
				'txnSubType' => '01',				//交易子类
				'bizType' => '000201',				//业务类型
				'frontUrl' =>  SDK_FRONT_NOTIFY_URL,  		//前台通知地址，控件接入的时候不会起作用
				'backUrl' => SDK_BACK_NOTIFY_URL,		//后台通知地址
				'signMethod' => '01',		//签名方法
				'channelType' => '08',		//渠道类型，07-PC，08-手机
				'accessType' => '0',		//接入类型
				'merId' => '888888888888888',	//商户代码，请改自己的测试商户号
				'orderId' => date('YmdHis'),	//商户订单号，8-40位数字字母
				'txnTime' => date('YmdHis'),	//订单发送时间
				'txnAmt' => '100',		//交易金额，单位分
				'currencyCode' => '156',	//交易币种
				'orderDesc' => '订单描述',  //订单描述，可不上送，上送时控件中会显示该信息
				'reqReserved' =>' 透传信息', //请求方保留域，透传字段，查询、通知、对账文件中均会原样出现
		);

	}

	/**
	 * 查询交易
	 */
	public function query(){
		$params = array(
				'version' => '5.0.0',		//版本号
				'encoding' => 'utf-8',		//编码方式
				'certId' => getSignCertId (),	//证书ID
				'signMethod' => '01',		//签名方法
				'txnType' => '00',		//交易类型
				'txnSubType' => '00',		//交易子类
				'bizType' => '000000',		//业务类型
				'accessType' => '0',		//接入类型
				'channelType' => '07',		//渠道类型
				'orderId' => '20150206215110',	//请修改被查询的交易的订单号
				'merId' => '888888888888888',	//商户代码，请修改为自己的商户号
				'txnTime' => '20150206212559',	//请修改被查询的交易的订单发送时间
		);
	}

	/**
	 * 文件传输类交易
	 */
	public function fileTransfer(){
		$params = array(
				'version' => '5.0.0',		//版本号
				'encoding' => 'utf-8',		//编码方式
				'certId' => getSignCertId (),	//证书ID
				'txnType' => '76',		//交易类型
				'signMethod' => '01',		//签名方法
				'txnSubType' => '01',		//交易子类
				'bizType' => '000000',		//业务类型
				'accessType' => '0',		//接入类型
				'merId' => '700000000000001',	                //商户代码，请替换实际商户号测试，如使用的是自助化平台注册的商户号，该商户号没有权限测文件下载接口的，请使用测试参数里写的文件下载的商户号和日期测。如需真实交易文件，请使用自助化平台下载文件。
				'settleDate' => '0119',		//清算日期
				'txnTime' => date('YmdHis'),	//订单发送时间
				'fileType' => '00',		//文件类型
		);
	}

	/**
	 * 预授权
	 */
	public function authDealApp(){
		$params = array(
				'version' => '5.0.0',				//版本号
				'encoding' => 'utf-8',				//编码方式
				'certId' => getSignCertId (),			//证书ID
				'txnType' => '02',				//交易类型
				'txnSubType' => '01',				//交易子类 01：预授权、03：担保消费
				'bizType' => '000201',				//业务类型
				'frontUrl' =>  SDK_FRONT_NOTIFY_URL,  		//前台通知地址，控件接入的时候不会起作用
				'backUrl' => SDK_BACK_NOTIFY_URL,		//后台通知地址
				'signMethod' => '01',		//签名方法
				'channelType' => '08',		//渠道类型，07-PC，08-手机
				'accessType' => '0',		//接入类型
				'merId' => '888888888888888',	//商户代码，请改自己的测试商户号
				'orderId' => date('YmdHis'),	//商户订单号，8-40位数字字母
				'txnTime' => date('YmdHis'),	//订单发送时间
				'txnAmt' => '100',		//交易金额，单位分
				'currencyCode' => '156',	//交易币种
				'orderDesc' => '订单描述',  //订单描述，可不上送，上送时控件中会显示该信息
				'reqReserved' =>' 透传信息', //请求方保留域，透传字段，查询、通知、对账文件中均会原样出现
		);
	}
	/**
	 * 预授权
	 */
	public function authDeal(){
		$params = array(
				'version' => '5.0.0',				//版本号
				'encoding' => 'utf-8',				//编码方式
				'certId' => getSignCertId (),			//证书ID
				'txnType' => '02',				//交易类型
				'txnSubType' => '01',				//交易子类 01：预授权、03：担保消费
				'bizType' => '000201',				//业务类型
				'frontUrl' =>  SDK_FRONT_NOTIFY_URL,  		//前台通知地址
				'backUrl' => SDK_BACK_NOTIFY_URL,		//后台通知地址
				'signMethod' => '01',		//签名方法
				'channelType' => '08',		//渠道类型，07-PC，08-手机
				'accessType' => '0',		//接入类型
				'merId' => '888888888888888',	//商户代码，请改自己的测试商户号
				'orderId' => date('YmdHis'),	//商户订单号，8-40位数字字母
				'txnTime' => date('YmdHis'),	//订单发送时间
				'txnAmt' => '100',		//交易金额，单位分
				'currencyCode' => '156',	//交易币种
				'orderDesc' => '订单描述',  //订单描述，可不上送，上送时控件中会显示该信息
				'reqReserved' =>' 透传信息', //请求方保留域，透传字段，查询、通知、对账文件中均会原样出现
		);
	}

	/**
	 * 预授权撤销
	 */
	public function authDealUndo(){
		$params = array(
				'version' => '5.0.0',		//版本号
				'encoding' => 'utf-8',		//编码方式
				'certId' => getSignCertId (),	//证书ID
				'signMethod' => '01',		//签名方法
				'txnType' => '32',		//交易类型
				'txnSubType' => '00',		//交易子类
				'bizType' => '000201',		//业务类型
				'accessType' => '0',		//接入类型
				'channelType' => '07',		//渠道类型
				'orderId' => date('YmdHis'),	//商户订单号，重新产生，不同于原消费
				'merId' => '888888888888888',			//商户代码，请改成自己的测试商户号
				'origQryId' => '201502281106305523728',    //原预授权的queryId，可以从查询接口或者通知接口中获取
				'txnTime' => date('YmdHis'),	//订单发送时间，重新产生，不同于原交易
				'txnAmt' => '100',              //交易金额，需和原预授权一致
				'backUrl' => SDK_BACK_NOTIFY_URL,	   //后台通知地址
				'reqReserved' =>' 透传信息', //请求方保留域，透传字段，查询、通知、对账文件中均会原样出现
		);

	}

	/**
	 * 预授权完成
	 */
	public function authDealFinish(){
		$params = array(
				'version' => '5.0.0',		//版本号
				'encoding' => 'utf-8',		//编码方式
				'certId' => getSignCertId (),	//证书ID
				'signMethod' => '01',		//签名方法
				'txnType' => '03',		//交易类型
				'txnSubType' => '00',		//交易子类
				'bizType' => '000201',		//业务类型
				'accessType' => '0',		//接入类型
				'channelType' => '07',		//渠道类型
				'orderId' => date('YmdHis'),	//商户订单号，重新产生，不同于原消费
				'merId' => '888888888888888',			//商户代码，请改成自己的测试商户号
				'origQryId' => '201502281110005523968',    //原预授权的queryId，可以从查询接口或者通知接口中获取
				'txnTime' => date('YmdHis'),	//订单发送时间，重新产生，不同于原交易
				'txnAmt' => '100',              //交易金额，小于等于原预授权金额的115%
				'backUrl' => SDK_BACK_NOTIFY_URL,	   //后台通知地址
				'reqReserved' =>' 透传信息', //请求方保留域，透传字段，查询、通知、对账文件中均会原样出现
		);
	}

	/**
	 * 预授权完成撤销
	 */
	public function authDealFinishUndo(){
		$params = array(
				'version' => '5.0.0',		//版本号
				'encoding' => 'utf-8',		//编码方式
				'certId' => getSignCertId (),	//证书ID
				'signMethod' => '01',		//签名方法
				'txnType' => '33',		//交易类型
				'txnSubType' => '00',		//交易子类
				'bizType' => '000201',		//业务类型
				'accessType' => '0',		//接入类型
				'channelType' => '07',		//渠道类型
				'orderId' => date('YmdHis'),	//商户订单号，重新产生，不同于原消费
				'merId' => '888888888888888',			//商户代码，请改成自己的测试商户号
				'origQryId' => '201502281111295524048',    //原预授权完成的queryId，可以从查询接口或者通知接口中获取
				'txnTime' => date('YmdHis'),	//订单发送时间，重新产生，不同于原交易
				'txnAmt' => '100',              //交易金额，需和原预授权完成一致
				'backUrl' => SDK_BACK_NOTIFY_URL,	   //后台通知地址
				'reqReserved' =>' 透传信息', //请求方保留域，透传字段，查询、通知、对账文件中均会原样出现
		);
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

	/**
	 * 取验证签名证书
	 * @throws \InvalidArgumentException
	 * @return string
	 */
	private function getVerifyPublicKey(){
		//先判断配置的验签证书是否银联返回指定的证书是否一致
		if($this->getCertIdCer($this->config['verifyCertPath']) != $this->getSignCertId()) {
			throw new \InvalidArgumentException('Verify sign cert is incorrect');
		}
		return file_get_contents($this->config['verifyCertPath']);
	}

	/**
	 * 取.cer格式证书ID(SN)
	 * @return string
	 */
	private function getCertIdCer($path){
		$x509data = file_get_contents($path);
		openssl_x509_read($x509data);
		$certdata = openssl_x509_parse($x509data);
		return $certdata['serialNumber'];
	}

	private function post($url, $data) {
		$data["sign"] = $this->sign($data);
		$xml = $this->array2xml($data);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $url);
		$content = curl_exec($ch);
		$array = $this->xml2array($content);
		return $array;
	}
}