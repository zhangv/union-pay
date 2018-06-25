<?php
namespace zhangv\unionpay\service;
use zhangv\unionpay\UnionPay;

use \Exception;
/**
 * 代收
 * @license MIT
 * @author zhangv
 * @ref https://open.unionpay.com/ajweb/product/newProApiList?proId=68
 * @method mixed query($orderId, $txnTime, $ext = [])
 * @method mixed fileDownload($settleDate, $fileType)
 */
class DirectDebit extends B2C {

	/**
	 * 前台授权代收协议
	 * @param string $orderId
	 * @param string $accNo
	 * @param array $customerInfo
	 * @param array $ext
	 * @return string
	 */
	public function authorize($orderId, $accNo, $customerInfo, $ext = []) {
		$params = array_merge($this->commonParams(),[
			//基础参数
			'txnType' => UnionPay::TXNTYPE_AUTHORIZE,
			'txnSubType' => '11',
			'bizType' => UnionPay::BIZTYPE_DIRECTDEBIT,
			'accessType' => UnionPay::ACCESSTYPE_MERCHANT,
			'channelType' => UnionPay::CHANNELTYPE_PC,
			//交易参数
			'orderId' => $orderId,
			'frontUrl' => $this->config['returnUrl'],
			'accNo' => $accNo,
			'customerInfo' => $this->getCustomerInfo($customerInfo),
			'txnTime' => date('YmdHis'),
		],$ext);
		$params['signature'] = $this->sign($params);
		return $this->createPostForm($params);
	}

	/**
	 * 后台授权代收协议
	 * @param string $orderId
	 * @param string $accNo
	 * @param array $customerInfo
	 * @param array $ext
	 * @return array
	 */
	public function backAuthorize($orderId, $accNo, $customerInfo, $ext = []) {
		$params = array_merge($this->commonParams(),[
			//基础参数
			'txnType' => UnionPay::TXNTYPE_AUTHORIZE,
			'txnSubType' => '11',
			'bizType' => UnionPay::BIZTYPE_DIRECTDEBIT,
			'accessType' => UnionPay::ACCESSTYPE_MERCHANT,
			'channelType' => UnionPay::CHANNELTYPE_PC,
			//交易参数
			'orderId' => $orderId,
			'accNo' => $this->encryptData($accNo),
			'customerInfo' => $this->encryptCustomerInfo($customerInfo),
			'txnTime' => date('YmdHis'),
		],$ext);
		$params['signature'] = $this->sign($params);
		return $this->post($this->backTransUrl, $params);
	}

	/**
	 * 解除授权代收协议
	 * @param string $orderId
	 * @param string $accNo
	 * @param array $ext
	 * @return array
	 */
	public function unauthorize($orderId, $accNo, $ext = []) {
		$params = array_merge($this->commonParams(),[
			//基础参数
			'txnType' => UnionPay::TXNTYPE_UNAUTHORIZE,
			'txnSubType' => '04',
			'bizType' => UnionPay::BIZTYPE_DIRECTDEBIT,
			'accessType' => UnionPay::ACCESSTYPE_MERCHANT,
			'channelType' => UnionPay::CHANNELTYPE_PC,
			//交易参数
			'orderId' => $orderId,
			'frontUrl' => $this->config['returnUrl'],
			'accNo' => $this->encryptData($accNo),
			'txnTime' => date('YmdHis'),
		],$ext);
		$params['signature'] = $this->sign($params);
		return $this->post($this->backTransUrl, $params);
	}

	/**
	 * 代收
	 * @param $orderId
	 * @param $txnAmt
	 * @param array $ext
	 * @return string
	 */
	public function pay($orderId, $txnAmt, $ext = []) {
		$params = array_merge($this->commonParams(),[
			//基础参数
			'txnType' => UnionPay::TXNTYPE_DIRECTDEBIT,
			'txnSubType' => '00',
			'bizType' => UnionPay::BIZTYPE_DIRECTDEBIT,
			'accessType' => UnionPay::ACCESSTYPE_MERCHANT,
			'channelType' => UnionPay::CHANNELTYPE_PC,
			'currencyCode' => $this->config['currencyCode'],
			//交易参数
			'orderId' => $orderId,
			'frontUrl' => $this->config['returnUrl'],
			'txnTime' => date('YmdHis'),
			'txnAmt' => $txnAmt,
		],$ext);
		$params['signature'] = $this->sign($params);
		return $this->createPostForm($params);
	}

	/**
	 * 代收(使用绑定标识号)
	 * @param string $orderId
	 * @param string $txnAmt
	 * @param string $bindId
	 * @param array $ext
	 * @return string
	 */
	public function payByBindId($orderId, $txnAmt, $bindId, $ext = []) {
		$params = array_merge($this->commonParams(),[
			//基础参数
			'txnType' => UnionPay::TXNTYPE_DIRECTDEBIT,
			'txnSubType' => '00',
			'bizType' => UnionPay::BIZTYPE_DIRECTDEBIT,
			'accessType' => UnionPay::ACCESSTYPE_MERCHANT,
			'channelType' => UnionPay::CHANNELTYPE_PC,
			'currencyCode' => $this->config['currencyCode'],
			//交易参数
			'orderId' => $orderId,
			'txnTime' => date('YmdHis'),
			'txnAmt' => $txnAmt,
			'bindId' => $bindId,
		],$ext);
		$params['signature'] = $this->sign($params);
		return $this->createPostForm($params);
	}

	/**
	 * 消费撤销
	 * @param string $orderId
	 * @param string $origQryId
	 * @param string $txnAmt
	 * @param array $ext
	 * @return mixed
	 */
	public function payUndo($orderId, $origQryId, $txnAmt, $ext = []) {
		$ext['bizType'] = UnionPay::BIZTYPE_DIRECTDEBIT;
		return parent::payUndo($orderId, $origQryId, $txnAmt, $ext);
	}

	/**
	 * 退款
	 * @param $orderId
	 * @param $origQryId
	 * @param $refundAmt
	 * @param array $ext
	 * @return mixed
	 */
	public function refund($orderId, $origQryId, $refundAmt, $ext = []) {
		$ext['bizType'] = UnionPay::BIZTYPE_DIRECTDEBIT;
		return parent::refund($orderId, $origQryId, $refundAmt, $ext);
	}

	/**
	 * 后台绑定
	 * @param $orderId
	 * @param $accNo
	 * @param $customerInfo
	 * @param $bindId
	 * @param array $ext
	 * @return array
	 */
	public function backBind($orderId, $accNo, $customerInfo, $bindId, $ext = []) {
		$params = array_merge($this->commonParams(),[
			//基础参数
			'txnType' => UnionPay::TXNTYPE_AUTHORIZE,
			'txnSubType' => '01',
			'bizType' => UnionPay::BIZTYPE_DIRECTDEBIT,
			'accessType' => UnionPay::ACCESSTYPE_MERCHANT,
			'channelType' => UnionPay::CHANNELTYPE_PC,
			'currencyCode' => $this->config['currencyCode'],
			//交易参数
			'orderId' => $orderId,
			'accNo' => $this->encryptData($accNo),
			'customerInfo' => $this->encryptCustomerInfo($customerInfo),
			'bindId' => $bindId,
			'txnTime' => date('YmdHis'),
		],$ext);
		$params['signature'] = $this->sign($params);
		return $this->post($this->backTransUrl, $params);
	}

	/**
	 * 前台绑定
	 * @param $orderId
	 * @param $accNo
	 * @param $customerInfo
	 * @param $bindId
	 * @param array $ext
	 * @return string
	 */
	public function frontBind($orderId, $accNo, $customerInfo, $bindId, $ext = []) {
		$params = array_merge($this->commonParams(),[
			//基础参数
			'txnType' => UnionPay::TXNTYPE_AUTHORIZE,
			'txnSubType' => '01',
			'bizType' => UnionPay::BIZTYPE_DIRECTDEBIT,
			'accessType' => UnionPay::ACCESSTYPE_MERCHANT,
			'channelType' => UnionPay::CHANNELTYPE_PC,
			'currencyCode' => $this->config['currencyCode'],
			//交易参数
			'orderId' => $orderId,
			'accNo' => $this->encryptData($accNo),
			'customerInfo' => $this->encryptCustomerInfo($customerInfo),
			'bindId' => $bindId,
			'txnTime' => date('YmdHis'),
			'frontUrl' => $this->config['returnUrl'],
		],$ext);
		$params['signature'] = $this->sign($params);
		return $this->createPostForm($params, '绑定');
	}

	/**
	 * 查询绑定
	 * @param $orderId
	 * @param $bindId
	 * @param array $ext
	 * @return array
	 */
	public function queryBind($orderId, $bindId, $ext = []) {
		$params = array_merge($this->commonParams(),[
			//基础参数
			'txnType' => UnionPay::TXNTYPE_QUERYBIND,
			'txnSubType' => '00',
			'bizType' => UnionPay::BIZTYPE_DIRECTDEBIT,
			'accessType' => UnionPay::ACCESSTYPE_MERCHANT,
			'channelType' => UnionPay::CHANNELTYPE_PC,
			'currencyCode' => $this->config['currencyCode'],
			//交易参数
			'orderId' => $orderId,
			'bindId' => $bindId,
			'txnTime' => date('YmdHis'),
			'frontUrl' => $this->config['returnUrl'],
		],$ext);
		$params['signature'] = $this->sign($params);
		return $this->post($this->backTransUrl, $params);
	}

	/**
	 * 解除绑定
	 * @param $orderId
	 * @param $bindId
	 * @param array $ext
	 * @return array
	 */
	public function removeBind($orderId, $bindId, $ext = []) {
		$params = array_merge($this->commonParams(),[
			//基础参数
			'txnType' => UnionPay::TXNTYPE_UNAUTHORIZE,
			'txnSubType' => '00',
			'bizType' => UnionPay::BIZTYPE_DIRECTDEBIT,
			'accessType' => UnionPay::ACCESSTYPE_MERCHANT,
			'channelType' => UnionPay::CHANNELTYPE_PC,
			'currencyCode' => $this->config['currencyCode'],
			//交易参数
			'orderId' => $orderId,
			'bindId' => $bindId,
			'txnTime' => date('YmdHis'),
		],$ext);
		$params['signature'] = $this->sign($params);
		return $this->post($this->backTransUrl, $params);
	}

	/**
	 * 后台认证
	 * @param string $orderId
	 * @param string $accNo
	 * @param array $customerInfo
	 * @param array $ext
	 * @return array
	 */
	public function backAuthenticate($orderId, $accNo, $customerInfo, $ext = []) {
		$params = array_merge($this->commonParams(),[
			//基础参数
			'txnType' => UnionPay::TXNTYPE_AUTHORIZE,
			'txnSubType' => '01',
			'bizType' => UnionPay::BIZTYPE_DIRECTDEBIT,
			'accessType' => UnionPay::ACCESSTYPE_MERCHANT,
			'channelType' => UnionPay::CHANNELTYPE_PC,
			'currencyCode' => $this->config['currencyCode'],
			//交易参数
			'orderId' => $orderId,
			'accNo' => $this->encryptData($accNo),
			'customerInfo' => $this->encryptCustomerInfo($customerInfo),
			'txnTime' => date('YmdHis'),
		],$ext);
		$params['signature'] = $this->sign($params);
		return $this->post($this->backTransUrl, $params);
	}

	/**
	 * 前台认证
	 * @param string $orderId
	 * @param string $accNo
	 * @param array $customerInfo
	 * @param array $ext
	 * @return string
	 */
	public function frontAuthenticate($orderId, $accNo, $customerInfo, $ext = []) {
		$params = array_merge($this->commonParams(),[
			//基础参数
			'txnType' => UnionPay::TXNTYPE_AUTHORIZE,
			'txnSubType' => '10',
			'bizType' => UnionPay::BIZTYPE_DIRECTDEBIT,
			'accessType' => UnionPay::ACCESSTYPE_MERCHANT,
			'channelType' => UnionPay::CHANNELTYPE_PC,
			'currencyCode' => $this->config['currencyCode'],
			//交易参数
			'orderId' => $orderId,
			'accNo' => $this->encryptData($accNo),
			'customerInfo' => $this->encryptCustomerInfo($customerInfo),
			'txnTime' => date('YmdHis'),
			'reserved' =>'{checkFlag=11100}'
		],$ext);
		$params['signature'] = $this->sign($params);
		return $this->createPostForm($params, '认证');
	}

	/**
	 * 短信认证
	 * @param string $orderId
	 * @param string $accNo
	 * @param array $customerInfo
	 * @param array $ext
	 * @return array
	 */
	public function smsAuthenticate($orderId, $accNo, $customerInfo, $ext = []) {
		$params = array_merge($this->commonParams(),[
			//基础参数
			'txnType' => UnionPay::TXNTYPE_AUTHENTICATE,
			'txnSubType' => '01',
			'bizType' => UnionPay::BIZTYPE_DIRECTDEBIT,
			'accessType' => UnionPay::ACCESSTYPE_MERCHANT,
			'channelType' => UnionPay::CHANNELTYPE_PC,
			'currencyCode' => $this->config['currencyCode'],
			//交易参数
			'orderId' => $orderId,
			'accNo' => $this->encryptData($accNo),
			'customerInfo' => $this->encryptCustomerInfo($customerInfo),
			'txnTime' => date('YmdHis'),
		],$ext);
		$params['signature'] = $this->sign($params);
		return $this->post($this->backTransUrl, $params);
	}

	/**
	 * 批量代收
	 * @param string $orderId
	 * @param string $batchNo
	 * @param array $totalQty
	 * @param array $totalAmt
	 * @param string $filePath
	 * @param array $ext
	 * @throws Exception
	 * @return array
	 */
	public function batchPay($orderId, $batchNo, $totalQty, $totalAmt, $filePath, $ext = []) {
		if (!file_exists($filePath)) {
			throw new Exception("File path does not exists - $filePath");
		}
		$params = array_merge($this->commonParams(),[
			//基础参数
			'txnType' => UnionPay::TXNTYPE_BATCHDEBIT,
			'txnSubType' => '02',
			'bizType' => UnionPay::BIZTYPE_DIRECTDEBIT,
			'accessType' => UnionPay::ACCESSTYPE_MERCHANT,
			'channelType' => UnionPay::CHANNELTYPE_PC,
			'currencyCode' => $this->config['currencyCode'],
			//交易参数
			'orderId' => $orderId,
			'batchNo' => $batchNo,
			'totalQty' => $totalQty,
			'totalAmt' => $totalAmt,
			'fileContent' => $this->encodeFileContent($filePath),
			'txnTime' => date('YmdHis'),
		],$ext);
		$params['signature'] = $this->sign($params);
		return $this->post($this->backTransUrl, $params);
	}

	/**
	 * 查询批量支付交易
	 * @param string $batchNo
	 * @param array $ext
	 * @throws Exception
	 * @return array
	 */
	public function queryBatch($batchNo, $ext = []) {
		$params = array_merge($this->commonParams(),[
			//基础参数
			'txnType' => UnionPay::TXNTYPE_QUERYBATCHDEBIT,
			'txnSubType' => '02',
			'bizType' => UnionPay::BIZTYPE_DIRECTDEBIT,
			'accessType' => UnionPay::ACCESSTYPE_MERCHANT,
			'channelType' => UnionPay::CHANNELTYPE_PC,
			//交易参数
			'batchNo' => $batchNo,
			'txnTime' => date('YmdHis'),
		],$ext);
		$params['signature'] = $this->sign($params);
		return $this->post($this->backTransUrl, $params);
	}

}