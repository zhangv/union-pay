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
		$params = array_merge($this->commonParams(),[
			'txnType' => UnionPay::TXNTYPE_PAYBILL,
			'txnSubType' => '01',
			'bizType' => UnionPay::BIZTYPE_CHARGE,
			'accessType' => UnionPay::ACCESSTYPE_MERCHANT,
			'channelType' => UnionPay::CHANNELTYPE_MOBILE,
			'frontUrl' => $this->config['returnUrl'],

			'orderId' => $orderId,
			'txnTime' => date('YmdHis'),
			'txnAmt' => $txnAmt,
			'currencyCode' => $this->config['currencyCode'],
			'bussCode' => $bussCode,// 业务类型号，此处默认取demo演示页面传递的参数
			'payTimeout' => date('YmdHis', strtotime('+15 minutes'))
		],$ext);
		if($billQueryInfo){
			$params['billQueryInfo'] = base64_encode($billQueryInfo);// 账单要素，JSON格式
		}
		// 先查后缴送账单查询应答报文的queryId，直接缴费的不送
		if (array_key_exists("origQryId", $ext) && $ext ["origQryId"] != "") {
			$params ['origQryId'] = $ext ["origQryId"];
		}
		$params['signature'] = $this->sign($params);
		return $this->createPostForm($params, '银联缴费', $this->jfFrontTransUrl);
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
		$params = array_merge($this->commonParams(),[
			'txnType' => UnionPay::TXNTYPE_PAYBILL,
			'txnSubType' => '01',
			'bizType' => UnionPay::BIZTYPE_CHARGE,
			'accessType' => UnionPay::ACCESSTYPE_MERCHANT,
			'channelType' => UnionPay::CHANNELTYPE_MOBILE,

			'orderId' => $orderId,
			'txnTime' => date('YmdHis'),
			'txnAmt' => $txnAmt,
			'currencyCode' => $this->config['currencyCode'],
			'bussCode' => $bussCode,// 业务类型号，此处默认取demo演示页面传递的参数
			'payTimeout' => date('YmdHis', strtotime('+15 minutes')),
			'accNo' => $this->encryptData($accNo),
			'customerInfo' => $this->encryptCustomerInfo($customerInfo)
		],$ext);
		if($billQueryInfo){
			$params['billQueryInfo'] = base64_encode($billQueryInfo);// 账单要素，JSON格式
		}

		// 先查后缴送账单查询应答报文的queryId，直接缴费的不送
		if (array_key_exists("origQryId", $ext) && $ext ["origQryId"] != "") {
			$params ['origQryId'] = $ext ["origQryId"];
		}
		$params['signature'] = $this->sign($params);
		return $this->post($this->jfBackTransUrl, $params);
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
		$params = array_merge($this->commonParams(),[
			'txnType' => UnionPay::TXNTYPE_PAYBILL,
			'txnSubType' => '01',
			'bizType' => UnionPay::BIZTYPE_CHARGE,
			'accessType' => UnionPay::ACCESSTYPE_MERCHANT,
			'channelType' => UnionPay::CHANNELTYPE_MOBILE,

			'orderId' => $orderId,
			'txnTime' => date('YmdHis'),
			'txnAmt' => $txnAmt,
			'currencyCode' => $this->config['currencyCode'],
			'bussCode' => $bussCode,// 业务类型号
		],$ext);
		if($billQueryInfo){
			$params['billQueryInfo'] = base64_encode($billQueryInfo);// 账单要素，JSON格式
		}

		// 先查后缴送账单查询应答报文的queryId，直接缴费的不送
		if (array_key_exists("origQryId", $ext) && $ext ["origQryId"] != "") {
			$params ['origQryId'] = $ext ["origQryId"];
		}

		$params['signature'] = $this->sign($params);
		return $this->post($this->jfAppTransUrl, $params);
	}

	/**
	 * 账单查询
	 * @param $orderId
	 * @param $bussCode
	 * @param string $billQueryInfo 账单要素, JSON格式
	 * @param array $ext
	 * @return array
	 */
	public function queryBill($orderId, $bussCode, $billQueryInfo, $ext = []) {
		$params = array_merge([
			'version' => $this->config['version'],
			'signMethod' =>  $this->config['signMethod'],
			'encoding' => $this->config['encoding'],
			'encryptCertId' => $this->getCertIdCer($this->config['encryptCertPath']),
			'merId' => $this->config['merId'],
			'certId' => $this->getSignCertId(),

			'txnType' => UnionPay::TXNTYPE_QUERYBILL,
			'txnSubType' => '01',
			'bizType' => UnionPay::BIZTYPE_CHARGE,
			'accessType' => UnionPay::ACCESSTYPE_MERCHANT,
			'channelType' => UnionPay::CHANNELTYPE_MOBILE,

			'orderId' => $orderId,
			'txnTime' => date('YmdHis'),
			'bussCode' => $bussCode,// 业务类型号
		],$ext);
		if($billQueryInfo){
			$params['billQueryInfo'] = base64_encode($billQueryInfo);
		}

		// 多次查询送上一笔账单查询应答报文的queryId，正常情况不送。
		if (array_key_exists("origQryId", $ext) && $ext ["origQryId"] != "") {
			$params ['origQryId'] = $ext ["origQryId"];
		}

		$params['signature'] = $this->sign($params);
		return $this->post($this->jfBackTransUrl, $params);
	}

	/**
	 * 前台信用卡还款
	 * @param $orderId
	 * @param $txnAmt
	 * @param string $usr_num 信用卡号
	 * @param string $usr_nm 持卡人姓名
	 * @param array $ext
	 * @return string
	 */
	public function frontRepay($orderId, $txnAmt, $usr_num, $usr_nm, $ext = []) {
		$billQueryInfo = [
			"usr_num" => $usr_num,
			"usr_num2" => $usr_num,
			"usr_nm" => $usr_nm?:''
		];
		return $this->frontPayBill($orderId, $txnAmt, 'J1_9800_0000_1', json_encode($billQueryInfo), $ext);
	}

	/**
	 * 后台信用卡还款
	 * @param $orderId
	 * @param $txnAmt
	 * @param string $usr_num 信用卡号
	 * @param string $usr_nm 持卡人姓名
	 * @param $accNo
	 * @param $customerInfo
	 * @param array $ext
	 * @return array
	 */
	public function backRepay($orderId, $txnAmt, $usr_num, $usr_nm, $accNo, $customerInfo, $ext = []) {
		$billQueryInfo = [
			"usr_num" => $usr_num,
			"usr_num2" => $usr_num,
			"usr_nm" => $usr_nm?:''
		];
		return $this->backPayBill($orderId, $txnAmt, 'J1_9800_0000_1', json_encode($billQueryInfo), $accNo, $customerInfo, $ext);
	}

	/**
	 * 信用卡还款获取tn
	 * @param string $orderId
	 * @param string $txnAmt
	 * @param string $usr_num 信用卡号
	 * @param string $usr_nm 持卡人姓名
	 * @param array $ext
	 * @return array
	 */
	public function appRepay($orderId, $txnAmt, $usr_num, $usr_nm, $ext = []) {
		$billQueryInfo = [
			"usr_num" => $usr_num,
			"usr_num2" => $usr_num,
			"usr_nm" => $usr_nm?:''
		];
		return $this->appPayBill($orderId, $txnAmt, 'J1_9800_0000_1', json_encode($billQueryInfo), $ext);
	}

	/**
	 * 账单查询
	 * @param string $orderId
	 * @param string $usr_num 信用卡卡号
	 * @param string $query_month 账单月 YYYYMM
	 * @param string $usr_nm 信用卡持卡人姓名
	 * @param array $ext
	 * @return array
	 */
	public function queryRepay($orderId, $usr_num, $query_month, $usr_nm, $ext = []) {
		$billQueryInfo = [
			"usr_num" => $usr_num,
			"query_month" => $query_month,
			"usr_nm" => $usr_nm
		];
		return $this->queryBill($orderId, 'J1_9800_0000_1', json_encode($billQueryInfo), $ext);
	}

	/**
	 * 前台缴税
	 * @param $orderId
	 * @param $txnAmt
	 * @param $origQryId
	 * @param array $ext
	 * @return string
	 */
	public function frontPayTax($orderId, $txnAmt, $origQryId, $ext = []) {
		$ext['origQryId'] = $origQryId;
		return $this->frontPayBill($orderId,$txnAmt,'S0_9800_0000',null,$ext);
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
	public function backPayTax($orderId, $txnAmt, $origQryId, $accNo, $customerInfo, $ext = []) {
		$ext['origQryId'] = $origQryId;
		return $this->backPayBill($orderId,$txnAmt,'S0_9800_0000',null, $accNo, $customerInfo, $ext);
	}

	/**
	 * 缴税获取tn
	 * @param $orderId
	 * @param $txnAmt
	 * @param $origQryId
	 * @param array $ext
	 * @return array
	 */
	public function appPayTax($orderId, $txnAmt, $origQryId, $ext = []) {
		$ext['origQryId'] = $origQryId;
		return $this->appPayBill($orderId,$txnAmt,'S0_9800_0000',null, $ext);
	}

	/**
	 * 申报
	 * @param string    $orderId
	 * @param string    $usr_num 纳税人识别号
	 * @param string    $col_organ_cd 征收机关代码
	 * @param string    $col_voucher_no 应征凭证序号
	 * @param string    $proc_flg 流程标识
	 * @param array     $ext
	 * @return array
	 */
	public function queryTax($orderId, $usr_num, $col_organ_cd, $col_voucher_no, $proc_flg, $ext = []) {
		$billQueryInfo = [
			"usr_num" => $usr_num,
			"col_organ_cd" => $col_organ_cd,
			"col_voucher_no" => $col_voucher_no,
			"proc_flg" => $proc_flg,
		];
		return $this->queryBill($orderId,'S0_9800_0000', json_encode($billQueryInfo), $ext);
	}

	/**
	 * 交易状态查询
	 * @param string $orderId
	 * @param string $txnTime
	 * @param array $ext
	 * @return array
	 * @throws \Exception
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
		return $this->post($this->jfSingleQueryUrl, $params);
	}

	/**
	 * 获取地区列表
	 * @return mixed
	 */
	public function areas() {
		$url = "https://gateway.95516.com/jiaofei/config/s/areas";
		return $this->get($url);
	}

	/**
	 * 获取业务目录
	 * @return mixed
	 */
	public function categories($category) {
		$url = "https://gateway.95516.com/jiaofei/config/s/categories/{$category}";
		return $this->get($url);
	}

	/**
	 * 获取业务要素
	 * @return mixed
	 */
	public function biz($bussCode) {
		$url = "https://gateway.95516.com/jiaofei/config/s/biz/{$bussCode}";
		return $this->get($url);
	}

}