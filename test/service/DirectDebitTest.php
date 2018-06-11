<?php
require_once __DIR__ . "/../../demo/autoload.php";
use zhangv\unionpay\UnionPay;
use zhangv\unionpay\service\DirectDebit;
use PHPUnit\Framework\TestCase;

class DirectDebitTest extends TestCase{
	/** @var  DirectDebit */
	private $unionPay;
	private $config;
	public function setUp(){
		list($mode,$this->config) = include __DIR__ .'/../../demo/config-direct.php';
		$this->unionPay = UnionPay::DirectDebit($this->config,$mode);
	}

	/**
	 * @test
	 * @expectedException Exception
	 * @expectedExceptionMessage 6151050
	 */
	public function backAuthorize(){
		$orderId = date('YmdHis');
		$testAcc = $this->config['testAcc'][1];
		$accNo = $testAcc['accNo'];
		$customerInfo = array(
			'phoneNo' => $testAcc['phoneNo'], //手机号
			'cvn2' => $testAcc['cvn2'], //cvn2
			'expired' => $testAcc['expired'], //有效期，YYMM格式，持卡人卡面印的是MMYY的，请注意代码设置倒一下
			'smsCode' => '111111', //短信验证码
		);
		//无此交易权限
		$r = $this->unionPay->backAuthorize($orderId,$accNo,$customerInfo);
	}
}
