<?php
/**
 * Created by PhpStorm.
 * User: derekzhangv
 * Date: 01/02/2018
 * Time: 22:29
 */

use zhangv\unionpay\UnionPay;

class B2BTest extends PHPUnit\Framework\TestCase{
	/** @var  \zhangv\unionpay\service\B2B */
	private $unionPay;

	public function setUp(){
		list($mode,$config) = include __DIR__ .'/../../demo/config.php';
		$this->unionPay = UnionPay::B2B($config,$mode);
	}

	private static $outTradeNoOffset = 0;
	private function genOutTradeNo(){
		return time().(self::$outTradeNoOffset++);
	}
	/** @test */
	public function pay(){
		$orderId = $this->genOutTradeNo();
		$r = $this->unionPay->pay($orderId,1);
		$this->assertNotFalse(strpos($r,"https://cashier.test.95516.com/b2c/api/B2bPay.action"));
		$this->assertNotFalse(strpos($r,$orderId));
	}

}
