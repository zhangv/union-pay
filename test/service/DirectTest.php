<?php
/**
 * Created by PhpStorm.
 * User: derekzhangv
 * Date: 2018/4/10
 * Time: 10:52
 */
require_once __DIR__ . "/../../demo/autoload.php";
use zhangv\unionpay\UnionPay;
use PHPUnit\Framework\TestCase;

class DirectTest extends TestCase{
	/** @var  \zhangv\unionpay\service\Direct */
	private $unionPay;
	private $config;
	public function setUp(){
		list($mode,$this->config) = include __DIR__ .'/../../demo/config-direct.php';
		$this->unionPay = UnionPay::Direct($this->config,$mode);
	}

	/**
	 * 测试商户号仅支持前台开通，后台开通：无此交易权限。（需要用真实商户号测试）
	 * @test
	 * @expectedException Exception
	 */
	public function backOpen(){
		$orderId = date('YmdHis');
		$testAcc = $this->config['testAcc'][1];
		$accNo = $testAcc['accNo'];
		$customerInfo = array(
			'phoneNo' => $testAcc['phoneNo'], //手机号
			'cvn2' => $testAcc['cvn2'], //cvn2
			'expired' => $testAcc['expired'], //有效期，YYMM格式，持卡人卡面印的是MMYY的，请注意代码设置倒一下
			'smsCode' => '111111', //短信验证码
		);
		$r = $this->unionPay->backOpen($orderId,$accNo,$customerInfo);
	}

	/**
	 * @test
	 */
	public function queryOpen(){
		$orderId = date('YmdHis');
		$testAcc = $this->config['testAcc'][0];
		$accNo = $testAcc['accNo'];
		$r = $this->unionPay->queryOpen($orderId,$accNo);
		$this->assertEquals('1',$r['activateStatus']);
	}
	/**
	 * @test
	 */
	public function sms(){
		$orderId = date('YmdHis');
		$testAcc = $this->config['testAcc'][0];
		$accNo = $testAcc['accNo'];
		$customerInfo = array(
			'phoneNo' => $testAcc['phoneNo'],
		);
		$r = $this->unionPay->sms($orderId,$accNo,$customerInfo);
		$this->assertEquals('00',$r['respCode']);
	}

	/**
	 * @test
	 */
	public function pay(){
		$orderId = date('YmdHis');
		$testAcc = $this->config['testAcc'][2];
		$accNo = $testAcc['accNo'];
		$ext = [];
		$customeerInfo =  ['smsCode' => '111111'];
		$r = $this->unionPay->pay($orderId,1000,$accNo,$customeerInfo,$ext);
		$this->assertEquals('00',$r['respCode']);
	}

	/**
	 * 无此交易权限
	 * @test
	 * @expectedException Exception
	 * @expectedExceptionMessageRegExp  /无此交易权限/
	 */
	public function payByInstallment(){
		$orderId = date('YmdHis');
		$testAcc = $this->config['testAcc'][1];
		$accNo = $testAcc['accNo'];
		$customerInfo = ['smsCode' => '111111'];
		$ext = [
			'instalTransInfo' => $testAcc['instalTransInfo']
		];
		$this->unionPay->payByInstallment($orderId,100,$accNo,$customerInfo,$ext);
	}

}
