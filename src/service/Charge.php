<?php
namespace zhangv\unionpay\service;
use zhangv\unionpay\UnionPay;
/**
 * 缴费产品
 * @license MIT
 * @author zhangv
 * @ref https://open.unionpay.com/ajweb/product/newProApiList?proId=76
 * */
class Charge extends UnionPay {

	public function __construct($config, $mode = UnionPay::MODE_PROD) {
		parent::__construct($config, $mode);
		if ($mode == UnionPay::MODE_TEST) {
			$this->jfFrontTransUrl = 'https://gateway.test.95516.com/jiaofei/api/frontTransReq.do';
			$this->jfBackTransUrl = 'https://gateway.test.95516.com/jiaofei/api/backTransReq.do';
			$this->jfCardTransUrl = "https://gateway.95516.com/jiaofei/api/cardTransReq.do";
			$this->jfAppTransUrl = "https://gateway.test.95516.com/jiaofei/api/appTransReq.do";
			$this->jfSingleQueryUrl = 'https://gateway.test.95516.com/jiaofei/api/queryTrans.do';
		}
	}

	/**
	 * 前台账单缴费
	 * @param $orderId
	 * @param $txnAmt
	 * @param $bussCode
	 * @param $billQueryInfo
	 * @param array $ext
	 * @return string
	 */
	public function frontPayBill($orderId, $txnAmt, $bussCode, $billQueryInfo, $ext = []) {
		$params = [
			'version' => $this->config['version'],
			'encoding' => $this->config['encoding'],
			'signMethod' => UnionPay::SIGNMETHOD_RSA,
			'txnType' => UnionPay::TXNTYPE_PAYBILL,
			'txnSubType' => '01',
			'bizType' => UnionPay::BIZTYPE_CHARGE,
			'channelType' => UnionPay::CHANNELTYPE_MOBILE,
			'frontUrl' => $this->config['returnUrl'],
			'backUrl' => $this->config['notifyUrl'],
			'accessType' => '0', //接入类型
			'merId' => $this->config['merId'],
			'orderId' => $orderId,
			'txnTime' => date('YmdHis'),
			'txnAmt' => $txnAmt,
			'currencyCode' => '156',
			'encryptCertId' => $this->getCertIdCer($this->config['encryptCertPath']),
		];
		$params ['bussCode'] = $bussCode; // 业务类型号，此处默认取demo演示页面传递的参数
		$params ['billQueryInfo'] = base64_encode($billQueryInfo); // 账单要素，根据前文显示要素列表由用户填写值，JSON格式，此处默认取demo演示页面传递的参数
		if (array_key_exists("origQryId", $ext) && $ext ["origQryId"] != "") {
					$params ['origQryId'] = $ext ["origQryId"];
		}
		// 先查后缴送账单查询应答报文的queryId，直接缴费的不送

		$params['payTimeout'] = date('YmdHis', strtotime('+15 minutes'));
		$params['certId'] = $this->getSignCertId();
		$params = array_merge($params, $ext);
		$params['signature'] = $this->sign($params);
		return $this->createPostForm($params, '银联账单缴费', $this->jfFrontTransUrl);
	}

	/**
	 * 后台账单缴费
	 * @param $orderId
	 * @param $txnAmt
	 * @param $bussCode
	 * @param $billQueryInfo
	 * @param $accNo
	 * @param $customerInfo
	 * @param array $ext
	 * @return array
	 */
	public function backPayBill($orderId, $txnAmt, $bussCode, $billQueryInfo, $accNo, $customerInfo, $ext = []) {
		$params = [
			'version' => $this->config['version'],
			'encoding' => $this->config['encoding'],
			'signMethod' => UnionPay::SIGNMETHOD_RSA,
			'txnType' => UnionPay::TXNTYPE_PAYBILL,
			'txnSubType' => '01',
			'bizType' => UnionPay::BIZTYPE_CHARGE,
			'channelType' => UnionPay::CHANNELTYPE_MOBILE,
			'backUrl' => $this->config['notifyUrl'],
			'accessType' => '0', //接入类型
			'merId' => $this->config['merId'],
			'orderId' => $orderId,
			'txnTime' => date('YmdHis'),
			'txnAmt' => $txnAmt,
			'currencyCode' => '156',
			'encryptCertId' => $this->getCertIdCer($this->config['encryptCertPath']),
		];
		$params ['bussCode'] = $bussCode; // 业务类型号，此处默认取demo演示页面传递的参数
		$params ['billQueryInfo'] = base64_encode($billQueryInfo); // 账单要素，根据前文显示要素列表由用户填写值，JSON格式，此处默认取demo演示页面传递的参数
		if (array_key_exists("origQryId", $ext) && $ext ["origQryId"] != "") {
					$params ['origQryId'] = $ext ["origQryId"];
		}
		// 先查后缴送账单查询应答报文的queryId，直接缴费的不送

		$params['accNo'] = $this->encryptData($accNo);
		$params['customerInfo'] = $this->encryptCustomerInfo($customerInfo);

		$params['payTimeout'] = date('YmdHis', strtotime('+15 minutes'));
		$params['certId'] = $this->getSignCertId();
		$params = array_merge($params, $ext);
		$params['signature'] = $this->sign($params);
		return $this->post($params, $this->jfBackTransUrl);
	}

	/**
	 * 账单缴费获取tn
	 * @param $orderId
	 * @param $txnAmt
	 * @param $bussCode
	 * @param $billQueryInfo
	 * @param array $ext
	 * @return array
	 */
	public function appPayBill($orderId, $txnAmt, $bussCode, $billQueryInfo, $ext = []) {
		$params = [
			'version' => $this->config['version'],
			'encoding' => $this->config['encoding'],
			'signMethod' => UnionPay::SIGNMETHOD_RSA,
			'txnType' => UnionPay::TXNTYPE_PAYBILL,
			'txnSubType' => '01',
			'bizType' => UnionPay::BIZTYPE_CHARGE,
			'channelType' => UnionPay::CHANNELTYPE_MOBILE,
			'backUrl' => $this->config['notifyUrl'],
			'accessType' => '0', //接入类型
			'merId' => $this->config['merId'],
			'orderId' => $orderId,
			'txnTime' => date('YmdHis'),
			'txnAmt' => $txnAmt,
			'currencyCode' => '156',
			'encryptCertId' => $this->getCertIdCer($this->config['encryptCertPath']),
		];
		$params ['bussCode'] = $bussCode; // 业务类型号，此处默认取demo演示页面传递的参数
		$params ['billQueryInfo'] = base64_encode($billQueryInfo); // 账单要素，根据前文显示要素列表由用户填写值，JSON格式，此处默认取demo演示页面传递的参数
		if (array_key_exists("origQryId", $ext) && $ext ["origQryId"] != "") {
					$params ['origQryId'] = $ext ["origQryId"];
		}
		// 先查后缴送账单查询应答报文的queryId，直接缴费的不送

		$params['certId'] = $this->getSignCertId();
		$params = array_merge($params, $ext);
		$params['signature'] = $this->sign($params);
		return $this->post($params, $this->jfAppTransUrl);
	}

	/**
	 * 账单查询
	 * @param $orderId
	 * @param $txnAmt
	 * @param $bussCode
	 * @param $billQueryInfo
	 * @param $accNo
	 * @param $customerInfo
	 * @param array $ext
	 * @return array
	 */
	public function queryBill($orderId, $txnAmt, $bussCode, $billQueryInfo, $accNo, $customerInfo, $ext = []) {
		$params = [
			'version' => $this->config['version'],
			'encoding' => $this->config['encoding'],
			'signMethod' => UnionPay::SIGNMETHOD_RSA,
			'txnType' => UnionPay::TXNTYPE_PAYBILL,
			'txnSubType' => '01',
			'bizType' => UnionPay::BIZTYPE_CHARGE,
			'channelType' => UnionPay::CHANNELTYPE_MOBILE,
			'backUrl' => $this->config['notifyUrl'],
			'accessType' => '0', //接入类型
			'merId' => $this->config['merId'],
			'orderId' => $orderId,
			'txnTime' => date('YmdHis'),
			'txnAmt' => $txnAmt,
			'currencyCode' => '156',
			'encryptCertId' => $this->getCertIdCer($this->config['encryptCertPath']),
		];
		$params ['bussCode'] = $bussCode; // 业务类型号，此处默认取demo演示页面传递的参数
		$params ['billQueryInfo'] = base64_encode($billQueryInfo); // 账单要素，根据前文显示要素列表由用户填写值，JSON格式，此处默认取demo演示页面传递的参数
		if (array_key_exists("origQryId", $ext) && $ext ["origQryId"] != "") {
					$params ['origQryId'] = $ext ["origQryId"];
		}
		// 先查后缴送账单查询应答报文的queryId，直接缴费的不送

		$params['accNo'] = $this->encryptData($accNo);
		$params['customerInfo'] = $this->encryptCustomerInfo($customerInfo);

		$params['certId'] = $this->getSignCertId();
		$params = array_merge($params, $ext);
		$params['signature'] = $this->sign($params);
		return $this->post($params, $this->jfBackTransUrl);
	}

	/**
	 * 前台信用卡还款
	 * @param $orderId
	 * @param $txnAmt
	 * @param $billQueryInfo
	 * @param array $ext
	 * @return string
	 */
	public function frontRepay($orderId, $txnAmt, $billQueryInfo, $ext = []) {
		$params = [
			'version' => $this->config['version'],
			'encoding' => $this->config['encoding'],
			'signMethod' => UnionPay::SIGNMETHOD_RSA,
			'txnType' => UnionPay::TXNTYPE_PAYBILL,
			'txnSubType' => '01',
			'bizType' => UnionPay::BIZTYPE_CHARGE,
			'channelType' => UnionPay::CHANNELTYPE_MOBILE,
			'frontUrl' => $this->config['returnUrl'],
			'backUrl' => $this->config['notifyUrl'],
			'accessType' => '0', //接入类型
			'merId' => $this->config['merId'],
			'orderId' => $orderId,
			'txnTime' => date('YmdHis'),
			'txnAmt' => $txnAmt,
			'currencyCode' => '156',
			'encryptCertId' => $this->getCertIdCer($this->config['encryptCertPath']),
		];
		$params ['bussCode'] = 'J1_9800_0000_1'; // 业务类型号，此处默认取demo演示页面传递的参数
		$params ['billQueryInfo'] = base64_encode($billQueryInfo); // 账单要素，根据前文显示要素列表由用户填写值，JSON格式，此处默认取demo演示页面传递的参数
		if (array_key_exists("origQryId", $ext) && $ext ["origQryId"] != "") {
					$params ['origQryId'] = $ext ["origQryId"];
		}
		// 先查后缴送账单查询应答报文的queryId，直接缴费的不送

		$params['payTimeout'] = date('YmdHis', strtotime('+15 minutes'));
		$params['certId'] = $this->getSignCertId();
		$params = array_merge($params, $ext);
		$params['signature'] = $this->sign($params);
		return $this->createPostForm($params, '银联信用卡还款', $this->jfFrontTransUrl);
	}

	/**
	 * 后台信用卡还款
	 * @param $orderId
	 * @param $txnAmt
	 * @param $billQueryInfo
	 * @param $accNo
	 * @param $customerInfo
	 * @param array $ext
	 * @return array
	 */
	public function backRepay($orderId, $txnAmt, $billQueryInfo, $accNo, $customerInfo, $ext = []) {
		$params = [
			'version' => $this->config['version'],
			'encoding' => $this->config['encoding'],
			'signMethod' => UnionPay::SIGNMETHOD_RSA,
			'txnType' => UnionPay::TXNTYPE_PAYBILL,
			'txnSubType' => '01',
			'bizType' => UnionPay::BIZTYPE_CHARGE,
			'channelType' => UnionPay::CHANNELTYPE_MOBILE,
			'backUrl' => $this->config['notifyUrl'],
			'accessType' => '0', //接入类型
			'merId' => $this->config['merId'],
			'orderId' => $orderId,
			'txnTime' => date('YmdHis'),
			'txnAmt' => $txnAmt,
			'currencyCode' => '156',
			'encryptCertId' => $this->getCertIdCer($this->config['encryptCertPath']),
		];
		$params ['bussCode'] = 'J1_9800_0000_1';
		$params ['billQueryInfo'] = base64_encode($billQueryInfo); // 账单要素，根据前文显示要素列表由用户填写值，JSON格式，此处默认取demo演示页面传递的参数
		if (array_key_exists("origQryId", $ext) && $ext ["origQryId"] != "") {
					$params ['origQryId'] = $ext ["origQryId"];
		}
		// 先查后缴送账单查询应答报文的queryId，直接缴费的不送

		$params['accNo'] = $this->encryptData($accNo);
		$params['customerInfo'] = $this->encryptCustomerInfo($customerInfo);

		$params['payTimeout'] = date('YmdHis', strtotime('+15 minutes'));
		$params['certId'] = $this->getSignCertId();
		$params = array_merge($params, $ext);
		$params['signature'] = $this->sign($params);
		return $this->post($params, $this->jfBackTransUrl);
	}

	/**
	 * 信用卡还款获取tn
	 * @param $orderId
	 * @param $txnAmt
	 * @param $billQueryInfo
	 * @param array $ext
	 * @return array
	 */
	public function appRepay($orderId, $txnAmt, $billQueryInfo, $ext = []) {
		$params = [
			'version' => $this->config['version'],
			'encoding' => $this->config['encoding'],
			'signMethod' => UnionPay::SIGNMETHOD_RSA,
			'txnType' => UnionPay::TXNTYPE_PAYBILL,
			'txnSubType' => '01',
			'bizType' => UnionPay::BIZTYPE_CHARGE,
			'channelType' => UnionPay::CHANNELTYPE_MOBILE,
			'backUrl' => $this->config['notifyUrl'],
			'accessType' => '0', //接入类型
			'merId' => $this->config['merId'],
			'orderId' => $orderId,
			'txnTime' => date('YmdHis'),
			'txnAmt' => $txnAmt,
			'currencyCode' => '156',
			'encryptCertId' => $this->getCertIdCer($this->config['encryptCertPath']),
		];
		$params ['bussCode'] = 'J1_9800_0000_1';
		$params ['billQueryInfo'] = base64_encode($billQueryInfo); // 账单要素，根据前文显示要素列表由用户填写值，JSON格式，此处默认取demo演示页面传递的参数
		if (array_key_exists("origQryId", $ext) && $ext ["origQryId"] != "") {
					$params ['origQryId'] = $ext ["origQryId"];
		}
		// 先查后缴送账单查询应答报文的queryId，直接缴费的不送

		$params['certId'] = $this->getSignCertId();
		$params = array_merge($params, $ext);
		$params['signature'] = $this->sign($params);
		return $this->post($params, $this->jfAppTransUrl);
	}

	/**
	 * 账单查询
	 * @param $orderId
	 * @param $txnAmt
	 * @param $bussCode
	 * @param $billQueryInfo
	 * @param $accNo
	 * @param $customerInfo
	 * @param array $ext
	 * @return array
	 */
	public function queryRepay($orderId, $txnAmt, $bussCode, $billQueryInfo, $accNo, $customerInfo, $ext = []) {
		$params = [
			'version' => $this->config['version'],
			'encoding' => $this->config['encoding'],
			'signMethod' => UnionPay::SIGNMETHOD_RSA,
			'txnType' => UnionPay::TXNTYPE_PAYBILL,
			'txnSubType' => '01',
			'bizType' => UnionPay::BIZTYPE_CHARGE,
			'channelType' => UnionPay::CHANNELTYPE_MOBILE,
			'backUrl' => $this->config['notifyUrl'],
			'accessType' => '0', //接入类型
			'merId' => $this->config['merId'],
			'orderId' => $orderId,
			'txnTime' => date('YmdHis'),
			'txnAmt' => $txnAmt,
			'currencyCode' => '156',
			'encryptCertId' => $this->getCertIdCer($this->config['encryptCertPath']),
		];
		$params ['bussCode'] = $bussCode; // 业务类型号，此处默认取demo演示页面传递的参数
		$params ['billQueryInfo'] = base64_encode($billQueryInfo); // 账单要素，根据前文显示要素列表由用户填写值，JSON格式，此处默认取demo演示页面传递的参数
		if (array_key_exists("origQryId", $ext) && $ext ["origQryId"] != "") {
					$params ['origQryId'] = $ext ["origQryId"];
		}
		// 先查后缴送账单查询应答报文的queryId，直接缴费的不送

		$params['accNo'] = $this->encryptData($accNo);
		$params['customerInfo'] = $this->encryptCustomerInfo($customerInfo);

		$params['certId'] = $this->getSignCertId();
		$params = array_merge($params, $ext);
		$params['signature'] = $this->sign($params);
		return $this->post($params, $this->jfBackTransUrl);
	}

	/**
	 * 前台缴税
	 * @param $orderId
	 * @param $txnAmt
	 * @param array $ext
	 * @return string
	 */
	public function frontPayTax($orderId, $txnAmt, $ext = []) {
		$params = [
			'version' => $this->config['version'],
			'encoding' => $this->config['encoding'],
			'signMethod' => UnionPay::SIGNMETHOD_RSA,
			'txnType' => UnionPay::TXNTYPE_PAYBILL,
			'txnSubType' => '02',
			'bizType' => UnionPay::BIZTYPE_CHARGE,
			'channelType' => UnionPay::CHANNELTYPE_MOBILE,
			'frontUrl' => $this->config['returnUrl'],
			'backUrl' => $this->config['notifyUrl'],
			'accessType' => '0', //接入类型
			'merId' => $this->config['merId'],
			'orderId' => $orderId,
			'txnTime' => date('YmdHis'),
			'txnAmt' => $txnAmt,
			'currencyCode' => '156',
			'encryptCertId' => $this->getCertIdCer($this->config['encryptCertPath']),
		];
		$params ['bussCode'] = 'S0_9800_0000'; // 业务类型号，此处默认取demo演示页面传递的参数
		if (array_key_exists("origQryId", $ext) && $ext ["origQryId"] != "") {
					$params ['origQryId'] = $ext ["origQryId"];
		}
		// 先查后缴送账单查询应答报文的queryId，直接缴费的不送

		$params['payTimeout'] = date('YmdHis', strtotime('+15 minutes'));
		$params['certId'] = $this->getSignCertId();
		$params = array_merge($params, $ext);
		$params['signature'] = $this->sign($params);
		return $this->createPostForm($params, '银联缴税', $this->jfFrontTransUrl);
	}

	/**
	 * 后台缴税
	 * @param $orderId
	 * @param $txnAmt
	 * @param $accNo
	 * @param $customerInfo
	 * @param array $ext
	 * @return array
	 */
	public function backPayTax($orderId, $txnAmt, $accNo, $customerInfo, $ext = []) {
		$params = [
			'version' => $this->config['version'],
			'encoding' => $this->config['encoding'],
			'signMethod' => UnionPay::SIGNMETHOD_RSA,
			'txnType' => UnionPay::TXNTYPE_PAYBILL,
			'txnSubType' => '02',
			'bizType' => UnionPay::BIZTYPE_CHARGE,
			'channelType' => UnionPay::CHANNELTYPE_MOBILE,
			'frontUrl' => $this->config['returnUrl'],
			'backUrl' => $this->config['notifyUrl'],
			'accessType' => '0', //接入类型
			'merId' => $this->config['merId'],
			'orderId' => $orderId,
			'txnTime' => date('YmdHis'),
			'txnAmt' => $txnAmt,
			'currencyCode' => '156',
			'encryptCertId' => $this->getCertIdCer($this->config['encryptCertPath']),
		];
		$params ['bussCode'] = 'S0_9800_0000';
		if (array_key_exists("origQryId", $ext) && $ext ["origQryId"] != "") {
					$params ['origQryId'] = $ext ["origQryId"];
		}
		// 先查后缴送账单查询应答报文的queryId，直接缴费的不送

		$params['accNo'] = $this->encryptData($accNo);
		$params['customerInfo'] = $this->encryptCustomerInfo($customerInfo);

		$params['payTimeout'] = date('YmdHis', strtotime('+15 minutes'));
		$params['certId'] = $this->getSignCertId();
		$params = array_merge($params, $ext);
		$params['signature'] = $this->sign($params);
		return $this->post($params, $this->jfBackTransUrl);
	}

	/**
	 * 缴税获取tn
	 * @param $orderId
	 * @param $txnAmt
	 * @param array $ext
	 * @return array
	 */
	public function appPayTax($orderId, $txnAmt, $ext = []) {
		$params = [
			'version' => $this->config['version'],
			'encoding' => $this->config['encoding'],
			'signMethod' => UnionPay::SIGNMETHOD_RSA,
			'txnType' => UnionPay::TXNTYPE_PAYBILL,
			'txnSubType' => '02',
			'bizType' => UnionPay::BIZTYPE_CHARGE,
			'channelType' => UnionPay::CHANNELTYPE_MOBILE,
			'frontUrl' => $this->config['returnUrl'],
			'backUrl' => $this->config['notifyUrl'],
			'accessType' => '0', //接入类型
			'merId' => $this->config['merId'],
			'orderId' => $orderId,
			'txnTime' => date('YmdHis'),
			'txnAmt' => $txnAmt,
			'currencyCode' => '156',
			'encryptCertId' => $this->getCertIdCer($this->config['encryptCertPath']),
		];
		$params ['bussCode'] = 'S0_9800_0000';
		if (array_key_exists("origQryId", $ext) && $ext ["origQryId"] != "") {
					$params ['origQryId'] = $ext ["origQryId"];
		}
		// 先查后缴送账单查询应答报文的queryId，直接缴费的不送

		$params['certId'] = $this->getSignCertId();
		$params = array_merge($params, $ext);
		$params['signature'] = $this->sign($params);
		return $this->post($params, $this->jfAppTransUrl);
	}

	/**
	 * 申报
	 * @param $orderId
	 * @param $txnAmt
	 * @param $billQueryInfo
	 * @param array $ext
	 * @return array
	 */
	public function queryTax($orderId, $txnAmt, $billQueryInfo, $ext = []) {
		$params = [
			'version' => $this->config['version'],
			'encoding' => $this->config['encoding'],
			'signMethod' => UnionPay::SIGNMETHOD_RSA,
			'txnType' => UnionPay::TXNTYPE_QUERYTAX,
			'txnSubType' => '02',
			'bizType' => UnionPay::BIZTYPE_CHARGE,
			'channelType' => UnionPay::CHANNELTYPE_PC,
			'backUrl' => $this->config['notifyUrl'],
			'accessType' => '0', //接入类型
			'merId' => $this->config['merId'],
			'orderId' => $orderId,
			'txnTime' => date('YmdHis'),
			'txnAmt' => $txnAmt,
			'currencyCode' => '156',
			'encryptCertId' => $this->getCertIdCer($this->config['encryptCertPath']),
		];
		$params ['bussCode'] = 'S0_9800_0000'; // 业务类型号，此处默认取demo演示页面传递的参数
		$params ['billQueryInfo'] = base64_encode($billQueryInfo); // 账单要素，根据前文显示要素列表由用户填写值，JSON格式，此处默认取demo演示页面传递的参数

		$params['certId'] = $this->getSignCertId();
		$params = array_merge($params, $ext);
		$params['signature'] = $this->sign($params);
		return $this->post($params, $this->jfBackTransUrl);
	}

	/**
	 * 交易状态查询
	 * @param $orderId
	 * @param $txnTime
	 * @param array $ext
	 * @return array
	 */
	public function query($orderId, $txnTime, $ext = []) {
		$params = [
			'version' => $this->config['version'],
			'encoding' => $this->config['encoding'],
			'signMethod' => UnionPay::SIGNMETHOD_RSA,
			'txnType' => '00',
			'txnSubType' => '00',
			'bizType' => '000000',
			'channelType' => UnionPay::CHANNELTYPE_PC,
			'accessType' => '0', //接入类型
			'merId' => $this->config['merId'],
			'orderId' => $orderId,
			'txnTime' => $txnTime,
		];
		$params['certId'] = $this->getSignCertId();
		$params = array_merge($params, $ext);
		$params['signature'] = $this->sign($params);
		return $this->post($params, $this->jfSingleQueryUrl);
	}

	/**
	 * 获取地区列表
	 * @return mixed
	 */
	public function areas() {
		$url = "https://gateway.95516.com/jiaofei/config/s/areas";
		return $this->get([], $url);
	}

	/**
	 * 获取业务目录
	 * @return mixed
	 */
	public function categories($category) {
		$url = "https://gateway.95516.com/jiaofei/config/s/categories/{$category}";
		return $this->get([], $url);
	}

	/**
	 * 获取业务要素
	 * @return mixed
	 */
	public function biz($bussCode) {
		$url = "https://gateway.95516.com/jiaofei/config/s/biz/{$bussCode}";
		return $this->get([], $url);
	}

}